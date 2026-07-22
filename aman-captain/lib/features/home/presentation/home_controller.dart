import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/providers.dart';
import '../../../core/realtime/realtime_service.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../trip/domain/trip.dart';
import '../data/status_repository.dart';

final statusRepositoryProvider = Provider<StatusRepository>((ref) {
  return StatusRepository(ref.watch(apiClientProvider));
});

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  return RealtimeService(ref.watch(tokenStorageProvider));
});

class HomeState {
  const HomeState({
    this.isOnline = false,
    this.togglingOnline = false,
    this.currentOffer,
    this.error,
  });

  final bool isOnline;
  final bool togglingOnline;
  final TripOffer? currentOffer;
  final String? error;

  HomeState copyWith({bool? isOnline, bool? togglingOnline, TripOffer? currentOffer, bool clearOffer = false, String? error}) {
    return HomeState(
      isOnline: isOnline ?? this.isOnline,
      togglingOnline: togglingOnline ?? this.togglingOnline,
      currentOffer: clearOffer ? null : (currentOffer ?? this.currentOffer),
      error: error,
    );
  }
}

class HomeController extends StateNotifier<HomeState> {
  HomeController(this._statusRepository, this._realtimeService, this._captainId) : super(const HomeState()) {
    _init();
  }

  final StatusRepository _statusRepository;
  final RealtimeService _realtimeService;
  final int _captainId;

  StreamSubscription<Position>? _positionSub;
  Timer? _offerExpiryTimer;

  Future<void> _init() async {
    await _realtimeService.connect();
    await _realtimeService.subscribeToCaptainChannel(_captainId, _onOfferReceived);
  }

  void _onOfferReceived(TripOfferEvent event) {
    final offer = TripOffer.fromJson(event.data);
    state = state.copyWith(currentOffer: offer);

    // إزالة العرض تلقائيًا من الشاشة عند انتهاء مهلته (الخادم يتولى فعليًا الانتقال لمرشح آخر؛
    // هذا فقط يُخفي العرض القديم عن الكابتن محليًا حتى لا يحاول قبول عرض منتهي الصلاحية)
    _offerExpiryTimer?.cancel();
    _offerExpiryTimer = Timer(Duration(seconds: offer.offerTimeoutSeconds), () {
      if (state.currentOffer?.tripId == offer.tripId) {
        state = state.copyWith(clearOffer: true);
      }
    });
  }

  void clearOffer() {
    _offerExpiryTimer?.cancel();
    state = state.copyWith(clearOffer: true);
  }

  Future<void> toggleOnline(bool value) async {
    state = state.copyWith(togglingOnline: true, error: null);
    try {
      final confirmed = await _statusRepository.toggleOnline(value);
      state = state.copyWith(isOnline: confirmed, togglingOnline: false);
      if (confirmed) {
        _startLocationUpdates();
      } else {
        _stopLocationUpdates();
      }
    } catch (e) {
      state = state.copyWith(togglingOnline: false, error: 'تعذّر تحديث الحالة');
    }
  }

  Future<void> _startLocationUpdates() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      final requested = await Geolocator.requestPermission();
      if (requested == LocationPermission.denied) return;
    }

    _positionSub = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, distanceFilter: 20),
    ).listen((position) {
      _statusRepository.updateLocation(position.latitude, position.longitude);
    });
  }

  void _stopLocationUpdates() {
    _positionSub?.cancel();
    _positionSub = null;
  }

  @override
  void dispose() {
    _positionSub?.cancel();
    _offerExpiryTimer?.cancel();
    super.dispose();
  }
}

final homeControllerProvider = StateNotifierProvider<HomeController, HomeState>((ref) {
  final captain = ref.watch(authControllerProvider).captain;
  return HomeController(
    ref.watch(statusRepositoryProvider),
    ref.watch(realtimeServiceProvider),
    captain?.id ?? 0,
  );
});
