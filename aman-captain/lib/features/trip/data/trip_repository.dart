import '../../../core/network/api_client.dart';
import '../domain/trip.dart';

class TripRepository {
  TripRepository(this._client);

  final ApiClient _client;

  Future<Trip> accept(int tripId) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/accept'),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<void> reject(int tripId) {
    return _client.request((dio) => dio.post('/trips/$tripId/reject'), (_) => null);
  }

  Future<Trip> markArrived(int tripId) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/arrived'),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Trip> start(int tripId) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/start'),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Trip> complete(int tripId, {required double actualDistanceKm, required int actualDurationMin}) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/complete', data: {
        'actual_distance_km': actualDistanceKm,
        'actual_duration_min': actualDurationMin,
      }),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<List<Trip>> history() {
    return _client.request(
      (dio) => dio.get('/trips/history'),
      (data) => (data as List).map((e) => Trip.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}
