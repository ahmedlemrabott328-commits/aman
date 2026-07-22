import 'dart:convert';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';
import '../storage/token_storage.dart';

/// غلاف حول pusher_channels_flutter للاتصال بـ Laravel Reverb.
/// الزبون يشترك فقط في قناة الرحلة النشطة `trip.{id}` لتلقّي:
///  - trip.status.changed (قبول/وصول/بدء/إنهاء/إلغاء)
///  - captain.location.updated (تحريك أيقونة الكابتن على الخريطة لحظيًا)
///
/// ملاحظة توافق: التوقيعات مطابقة لإصدار pusher_channels_flutter ^2.4.1 وقت الكتابة.
class RealtimeService {
  RealtimeService(this._tokenStorage);

  final TokenStorage _tokenStorage;
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();
  bool _connected = false;

  Future<void> connect() async {
    if (_connected) return;

    await _pusher.init(
      apiKey: const String.fromEnvironment('REVERB_APP_KEY', defaultValue: ''),
      cluster: 'mt1',
      useTLS: const bool.fromEnvironment('REVERB_USE_TLS', defaultValue: false),
      host: const String.fromEnvironment('REVERB_HOST', defaultValue: '10.0.2.2'),
      port: const int.fromEnvironment('REVERB_PORT', defaultValue: 8080),
      onAuthorizer: _authorizer,
      onConnectionStateChange: (currentState, previousState) {},
      onError: (message, code, exception) {},
    );

    await _pusher.connect();
    _connected = true;
  }

  Future<dynamic> _authorizer(String channelName, String socketId, dynamic options) async {
    final token = await _tokenStorage.read();
    return jsonEncode({'auth': token});
  }

  Future<void> subscribeToTripChannel(
    int tripId, {
    required void Function(Map<String, dynamic>) onStatusChanged,
    required void Function(double lat, double lng) onLocationUpdated,
  }) async {
    await _pusher.subscribe(
      channelName: 'private-trip.$tripId',
      onEvent: (event) {
        final payload = jsonDecode(event.data) as Map<String, dynamic>;
        if (event.eventName == 'trip.status.changed') {
          onStatusChanged(payload);
        } else if (event.eventName == 'captain.location.updated') {
          onLocationUpdated((payload['lat'] as num).toDouble(), (payload['lng'] as num).toDouble());
        }
      },
    );
  }

  Future<void> unsubscribeFromTrip(int tripId) => _pusher.unsubscribe(channelName: 'private-trip.$tripId');

  Future<void> disconnect() async {
    await _pusher.disconnect();
    _connected = false;
  }
}
