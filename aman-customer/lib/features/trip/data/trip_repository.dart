import '../../../core/network/api_client.dart';
import '../domain/trip.dart';

class TripRepository {
  TripRepository(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> estimate({
    required int serviceId,
    required int cityId,
    required double distanceKm,
    required int durationMin,
  }) {
    return _client.request(
      (dio) => dio.post('/trips/estimate', data: {
        'service_id': serviceId,
        'city_id': cityId,
        'distance_km': distanceKm,
        'duration_min': durationMin,
      }),
      (data) => data as Map<String, dynamic>,
    );
  }

  Future<Trip> requestTrip({
    required int serviceId,
    required int cityId,
    required String pickupAddress,
    required double pickupLat,
    required double pickupLng,
    String? dropoffAddress,
    double? dropoffLat,
    double? dropoffLng,
    required double distanceKm,
    required int durationMin,
    String tripMode = 'instant',
    Map<String, dynamic>? delivery,
  }) {
    return _client.request(
      (dio) => dio.post('/trips', data: {
        'service_id': serviceId,
        'city_id': cityId,
        'trip_mode': tripMode,
        'pickup_address': pickupAddress,
        'pickup_lat': pickupLat,
        'pickup_lng': pickupLng,
        if (dropoffAddress != null) 'dropoff_address': dropoffAddress,
        if (dropoffLat != null) 'dropoff_lat': dropoffLat,
        if (dropoffLng != null) 'dropoff_lng': dropoffLng,
        'distance_km': distanceKm,
        'duration_min': durationMin,
        if (delivery != null) 'delivery': delivery,
      }),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Trip?> current() {
    return _client.request(
      (dio) => dio.get('/trips/current'),
      (data) => data == null ? null : Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Trip> show(int tripId) {
    return _client.request(
      (dio) => dio.get('/trips/$tripId'),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<Trip> cancel(int tripId, {String? reason}) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/cancel', data: {if (reason != null) 'reason': reason}),
      (data) => Trip.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<void> rate(int tripId, {required int score, String? comment}) {
    return _client.request(
      (dio) => dio.post('/trips/$tripId/rate', data: {'score': score, if (comment != null) 'comment': comment}),
      (_) => null,
    );
  }

  Future<List<Trip>> history() {
    return _client.request(
      (dio) => dio.get('/trips/history'),
      (data) => (data as List).map((e) => Trip.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}
