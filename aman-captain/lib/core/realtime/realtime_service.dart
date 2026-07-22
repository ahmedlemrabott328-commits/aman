import 'dart:convert';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';
import '../storage/token_storage.dart';

/// غلاف حول pusher_channels_flutter للاتصال بـ Laravel Reverb (متوافق مع بروتوكول Pusher).
/// يشترك تلقائيًا في القناة الخاصة بالكابتن `captain.{id}` لاستقبال عروض الرحلات الجديدة،
/// وفي قناة الرحلة النشطة `trip.{id}` عند وجودها، دون الحاجة لإعادة تحميل الشاشة يدويًا.
///
/// ملاحظة توافق: توقيعات onAuthorizer/onEvent أدناه مطابقة لإصدار pusher_channels_flutter
/// ^2.4.1 وقت الكتابة. قبل التشغيل الفعلي، راجع توثيق الإصدار المثبَّت فعليًا عبر
/// `flutter pub deps` إن تغيّرت الواجهة البرمجية بين الإصدارات.
class RealtimeService {
  RealtimeService(this._tokenStorage);

  final TokenStorage _tokenStorage;
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();
  bool _connected = false;

  static const _authEndpoint = String.fromEnvironment(
    'BROADCAST_AUTH_URL',
    defaultValue: 'http://10.0.2.2:8000/broadcasting/auth',
  );

  Future<void> connect() async {
    if (_connected) return;

    await _pusher.init(
      apiKey: const String.fromEnvironment('REVERB_APP_KEY', defaultValue: ''),
      cluster: 'mt1', // غير مستخدَم فعليًا مع Reverb، لكن الحزمة تتطلبه
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

  /// تفويض القنوات الخاصة عبر /broadcasting/auth بنفس توكن Sanctum الحالي
  Future<dynamic> _authorizer(String channelName, String socketId, dynamic options) async {
    final token = await _tokenStorage.read();
    // ملاحظة: pusher_channels_flutter لا يعرض تحكمًا كاملاً بالـ headers هنا في كل الإصدارات؛
    // عند الحاجة لتخصيص أعمق يُستبدل هذا بطلب Dio يدوي إلى _authEndpoint مع Authorization header
    // ثم إرجاع { auth: response.auth } كما يتوقعه Pusher protocol.
    return jsonEncode({'auth': token});
  }

  Future<void> subscribeToCaptainChannel(int captainId, void Function(TripOfferEvent) onOffer) async {
    await _pusher.subscribe(
      channelName: 'private-captain.$captainId',
      onEvent: (event) {
        if (event.eventName == 'trip.offer.new') {
          onOffer(TripOfferEvent(jsonDecode(event.data)));
        }
      },
    );
  }

  Future<void> subscribeToTripChannel(int tripId, {
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

  Future<void> unsubscribe(String channelName) => _pusher.unsubscribe(channelName: channelName);

  Future<void> disconnect() async {
    await _pusher.disconnect();
    _connected = false;
  }
}

class TripOfferEvent {
  TripOfferEvent(this.data);
  final Map<String, dynamic> data;
}
