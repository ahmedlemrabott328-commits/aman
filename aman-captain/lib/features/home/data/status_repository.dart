import '../../../core/network/api_client.dart';

class StatusRepository {
  StatusRepository(this._client);

  final ApiClient _client;

  Future<bool> toggleOnline(bool isOnline) {
    return _client.request(
      (dio) => dio.post('/status/toggle', data: {'is_online': isOnline}),
      (data) => data['is_online'] as bool,
    );
  }

  Future<void> updateLocation(double lat, double lng) {
    return _client.request(
      (dio) => dio.post('/status/location', data: {'lat': lat, 'lng': lng}),
      (_) => null,
    );
  }
}
