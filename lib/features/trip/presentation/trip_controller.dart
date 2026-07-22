import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../../../core/realtime/realtime_service.dart';
import '../data/trip_repository.dart';
import '../domain/trip.dart';

final tripRepositoryProvider = Provider<TripRepository>((ref) {
  return TripRepository(ref.watch(apiClientProvider));
});

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  return RealtimeService(ref.watch(tokenStorageProvider));
});

class ActiveTripState {
  const ActiveTripState({this.trip, this.captainLat, this.captainLng, this.loading = false, this.error});

  final Trip? trip;
  final double? captainLat;
  final double? captainLng;
  final bool loading;
  final String? error;

  ActiveTripState copyWith({
    Trip? trip, bool clearTrip = false, double? captainLat, double? captainLng, bool? loading, String? error,
  }) {
    return ActiveTripState(
      trip: clearTrip ? null : (trip ?? this.trip),
      captainLat: captainLat ?? this.captainLat,
      captainLng: captainLng ?? this.captainLng,
      loading: loading ?? this.loading,
      error: error,
    );
  }
}

class TripController extends StateNotifier<ActiveTripState> {
  TripController(this._repository, this._realtimeService) : super(const ActiveTripState());

  final TripRepository _repository;
  final RealtimeService _realtimeService;
  int? _subscribedTripId;

  /// يُستدعى عند فتح التطبيق لاسترجاع أي رحلة جارية بالفعل (بعد إغلاق التطبيق مثلاً)
  Future<void> restoreCurrentTrip() async {
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.current();
      if (trip != null) {
        state = state.copyWith(trip: trip, loading: false);
        _subscribeToTrip(trip.id);
      } else {
        state = state.copyWith(loading: false, clearTrip: true);
      }
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  void setTrip(Trip trip) {
    state = state.copyWith(trip: trip);
    _subscribeToTrip(trip.id);
  }

  void _subscribeToTrip(int tripId) {
    if (_subscribedTripId == tripId) return;
    _subscribedTripId = tripId;

    _realtimeService.connect().then((_) {
      _realtimeService.subscribeToTripChannel(
        tripId,
        onStatusChanged: (payload) async {
          // عند أي تغيّر حالة، نُعيد جلب الرحلة كاملة لضمان تحديث كل الحقول (السعر النهائي، بيانات الكابتن...)
          try {
            final refreshed = await _repository.show(tripId);
            state = state.copyWith(trip: refreshed);
          } on ApiException catch (_) {
            // فشل التحديث ليس حرجًا؛ ستُحدَّث الشاشة عند الحدث التالي أو عند فتحها يدويًا
          }
        },
        onLocationUpdated: (lat, lng) {
          state = state.copyWith(captainLat: lat, captainLng: lng);
        },
      );
    });
  }

  Future<void> cancel({String? reason}) async {
    if (state.trip == null) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.cancel(state.trip!.id, reason: reason);
      state = state.copyWith(trip: trip, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<bool> rate({required int score, String? comment}) async {
    if (state.trip == null) return false;
    try {
      await _repository.rate(state.trip!.id, score: score, comment: comment);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(error: e.message);
      return false;
    }
  }

  void clear() {
    if (_subscribedTripId != null) {
      _realtimeService.unsubscribeFromTrip(_subscribedTripId!);
      _subscribedTripId = null;
    }
    state = state.copyWith(clearTrip: true);
  }
}

final tripControllerProvider = StateNotifierProvider<TripController, ActiveTripState>((ref) {
  return TripController(ref.watch(tripRepositoryProvider), ref.watch(realtimeServiceProvider));
});
