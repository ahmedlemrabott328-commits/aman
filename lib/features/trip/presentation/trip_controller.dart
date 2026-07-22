import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../../../core/realtime/realtime_service.dart';
import '../../home/presentation/home_controller.dart';
import '../data/trip_repository.dart';
import '../domain/trip.dart';

final tripRepositoryProvider = Provider<TripRepository>((ref) {
  return TripRepository(ref.watch(apiClientProvider));
});

class ActiveTripState {
  const ActiveTripState({this.trip, this.loading = false, this.error});

  final Trip? trip;
  final bool loading;
  final String? error;

  ActiveTripState copyWith({Trip? trip, bool clearTrip = false, bool? loading, String? error}) {
    return ActiveTripState(
      trip: clearTrip ? null : (trip ?? this.trip),
      loading: loading ?? this.loading,
      error: error,
    );
  }
}

class TripController extends StateNotifier<ActiveTripState> {
  TripController(this._repository, this._realtimeService) : super(const ActiveTripState());

  final TripRepository _repository;
  final RealtimeService _realtimeService;

  Future<bool> accept(int tripId) async {
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.accept(tripId);
      state = state.copyWith(trip: trip, loading: false);
      await _realtimeService.subscribeToTripChannel(
        trip.id,
        onStatusChanged: (_) {}, // الحالة تُدار محليًا عبر استدعاءات الكابتن نفسه (arrived/start/complete)
        onLocationUpdated: (_, __) {}, // غير مستخدَم من طرف الكابتن (هو مصدر الموقع لا مستقبِله)
      );
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
      return false;
    }
  }

  Future<void> reject(int tripId) async {
    try {
      await _repository.reject(tripId);
    } on ApiException catch (_) {
      // فشل الرفض ليس حرجًا؛ العرض سينتهي محليًا بعد المهلة على أي حال
    }
  }

  Future<void> markArrived() async {
    if (state.trip == null) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.markArrived(state.trip!.id);
      state = state.copyWith(trip: trip, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<void> startTrip() async {
    if (state.trip == null) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.start(state.trip!.id);
      state = state.copyWith(trip: trip, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<void> completeTrip({required double actualDistanceKm, required int actualDurationMin}) async {
    if (state.trip == null) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final trip = await _repository.complete(
        state.trip!.id, actualDistanceKm: actualDistanceKm, actualDurationMin: actualDurationMin,
      );
      state = state.copyWith(trip: trip, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  void clear() => state = state.copyWith(clearTrip: true);
}

final tripControllerProvider = StateNotifierProvider<TripController, ActiveTripState>((ref) {
  return TripController(ref.watch(tripRepositoryProvider), ref.watch(realtimeServiceProvider));
});
