import '../../../core/network/api_client.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/customer.dart';

class AuthRepository {
  AuthRepository(this._client, this._tokenStorage);

  final ApiClient _client;
  final TokenStorage _tokenStorage;

  Future<void> sendOtp(String phone) {
    return _client.request(
      (dio) => dio.post('/auth/send-otp', data: {'phone': phone}),
      (_) => null,
    );
  }

  Future<Customer> verifyOtp({required String phone, required String code, String? fullName}) {
    return _client.request(
      (dio) => dio.post('/auth/verify-otp', data: {
        'phone': phone,
        'code': code,
        if (fullName != null) 'full_name': fullName,
      }),
      (data) async {
        await _tokenStorage.save(data['token'] as String);
        return Customer.fromJson(data['customer'] as Map<String, dynamic>);
      },
    );
  }

  Future<void> logout() async {
    try {
      await _client.request((dio) => dio.post('/auth/logout'), (_) => null);
    } finally {
      await _tokenStorage.clear();
    }
  }

  Future<bool> hasSession() async => (await _tokenStorage.read()) != null;
}
