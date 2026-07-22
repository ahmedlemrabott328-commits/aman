import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/network/api_exception.dart';
import '../../trip/data/trip_repository.dart';
import '../../trip/domain/trip.dart';
import '../../trip/presentation/trip_controller.dart' show tripRepositoryProvider;

// ملاحظة معروفة (راجع README): لا يوجد بعد GET /services أو GET /cities عام (غير إداري)
// في aman-backend، لذا الخدمات والمدينة الافتراضية مُثبَّتة هنا مؤقتًا.
class ServiceOption {
  const ServiceOption(this.id, this.code, this.label);
  final int id;
  final String code;
  final String label;
}

const kServices = [
  ServiceOption(1, 'ride', 'نقل ركاب'),
  ServiceOption(2, 'airport', 'خدمة المطار'),
  ServiceOption(3, 'delivery', 'توصيل'),
];
const kDefaultCityId = 1; // نواكشوط، حسب ترتيب ServicesAndCitiesSeeder

class LatLngPoint {
  const LatLngPoint(this.lat, this.lng);
  final double lat;
  final double lng;
}

class RequestState {
  const RequestState({
    this.pickupAddress,
    this.pickup,
    this.dropoffAddress,
    this.dropoff,
    this.selectedService = const ServiceOption(1, 'ride', 'نقل ركاب'),
    this.distanceKm,
    this.durationMin,
    this.estimatedPrice,
    this.currency,
    this.pricingRuleId,
    this.loadingEstimate = false,
    this.requesting = false,
    this.error,
  });

  final String? pickupAddress;
  final LatLngPoint? pickup;
  final String? dropoffAddress;
  final LatLngPoint? dropoff;
  final ServiceOption selectedService;
  final double? distanceKm;
  final int? durationMin;
  final double? estimatedPrice;
  final String? currency;
  final int? pricingRuleId;
  final bool loadingEstimate;
  final bool requesting;
  final String? error;

  bool get canRequest => pickup != null && dropoff != null && estimatedPrice != null;

  RequestState copyWith({
    String? pickupAddress, LatLngPoint? pickup, String? dropoffAddress, LatLngPoint? dropoff,
    ServiceOption? selectedService, double? distanceKm, int? durationMin,
    double? estimatedPrice, String? currency, int? pricingRuleId,
    bool? loadingEstimate, bool? requesting, String? error,
  }) {
    return RequestState(
      pickupAddress: pickupAddress ?? this.pickupAddress,
      pickup: pickup ?? this.pickup,
      dropoffAddress: dropoffAddress ?? this.dropoffAddress,
      dropoff: dropoff ?? this.dropoff,
      selectedService: selectedService ?? this.selectedService,
      distanceKm: distanceKm ?? this.distanceKm,
      durationMin: durationMin ?? this.durationMin,
      estimatedPrice: estimatedPrice ?? this.estimatedPrice,
      currency: currency ?? this.currency,
      pricingRuleId: pricingRuleId ?? this.pricingRuleId,
      loadingEstimate: loadingEstimate ?? this.loadingEstimate,
      requesting: requesting ?? this.requesting,
      error: error,
    );
  }
}

class RequestController extends StateNotifier<RequestState> {
  RequestController(this._repository) : super(const RequestState());

  final TripRepository _repository;

  void setPickup(String address, LatLngPoint point) {
    state = state.copyWith(pickupAddress: address, pickup: point);
  }

  void setService(ServiceOption service) {
    state = state.copyWith(selectedService: service);
    if (state.dropoff != null) _refreshEstimate();
  }

  Future<void> setDropoff(String address, LatLngPoint point) async {
    state = state.copyWith(dropoffAddress: address, dropoff: point, error: null);
    await _refreshEstimate();
  }

  Future<void> _refreshEstimate() async {
    if (state.pickup == null || state.dropoff == null) return;

    final distanceMeters = Geolocator.distanceBetween(
      state.pickup!.lat, state.pickup!.lng, state.dropoff!.lat, state.dropoff!.lng,
    );
    final distanceKm = distanceMeters / 1000;
    // تقدير مبدئي للمدة بافتراض سرعة متوسطة 28 كم/سا داخل المدينة؛ السعر النهائي
    // الفعلي يُعاد احتسابه في aman-backend من القيم الحقيقية عند إنهاء الرحلة على أي حال.
    final durationMin = ((distanceKm / 28) * 60).ceil().clamp(3, 240);

    state = state.copyWith(loadingEstimate: true, distanceKm: distanceKm, durationMin: durationMin, error: null);
    try {
      final result = await _repository.estimate(
        serviceId: state.selectedService.id,
        cityId: kDefaultCityId,
        distanceKm: distanceKm,
        durationMin: durationMin,
      );
      state = state.copyWith(
        loadingEstimate: false,
        estimatedPrice: (result['estimated_price'] as num).toDouble(),
        currency: result['currency'] as String,
        pricingRuleId: result['pricing_rule_id'] as int?,
      );
    } on ApiException catch (e) {
      state = state.copyWith(loadingEstimate: false, error: e.message);
    }
  }

  Future<Trip?> submit() async {
    if (!state.canRequest) return null;
    state = state.copyWith(requesting: true, error: null);
    try {
      final trip = await _repository.requestTrip(
        serviceId: state.selectedService.id,
        cityId: kDefaultCityId,
        pickupAddress: state.pickupAddress!,
        pickupLat: state.pickup!.lat,
        pickupLng: state.pickup!.lng,
        dropoffAddress: state.dropoffAddress,
        dropoffLat: state.dropoff!.lat,
        dropoffLng: state.dropoff!.lng,
        distanceKm: state.distanceKm!,
        durationMin: state.durationMin!,
      );
      state = state.copyWith(requesting: false);
      return trip;
    } on ApiException catch (e) {
      state = state.copyWith(requesting: false, error: e.message);
      return null;
    }
  }

  void reset() => state = const RequestState();
}

final requestControllerProvider = StateNotifierProvider<RequestController, RequestState>((ref) {
  return RequestController(ref.watch(tripRepositoryProvider));
});
