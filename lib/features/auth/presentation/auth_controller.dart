import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../data/auth_repository.dart';
import '../domain/captain.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(apiClientProvider), ref.watch(tokenStorageProvider));
});

enum AuthStep { unknown, unauthenticated, otpSent, authenticated }

class AuthState {
  const AuthState({
    this.step = AuthStep.unknown,
    this.phone,
    this.captain,
    this.loading = false,
    this.error,
  });

  final AuthStep step;
  final String? phone;
  final Captain? captain;
  final bool loading;
  final String? error;

  AuthState copyWith({
    AuthStep? step, String? phone, Captain? captain, bool? loading, String? error,
  }) {
    return AuthState(
      step: step ?? this.step,
      phone: phone ?? this.phone,
      captain: captain ?? this.captain,
      loading: loading ?? this.loading,
      error: error, // لا نُبقي رسالة الخطأ القديمة إلا إن مُرِّرت صراحة
    );
  }
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._repository) : super(const AuthState()) {
    _restoreSession();
  }

  final AuthRepository _repository;

  Future<void> _restoreSession() async {
    final hasSession = await _repository.hasSession();
    // ملاحظة: لا نملك endpoint "/me" حاليًا لاسترجاع بيانات الكابتن من التوكن مباشرة؛
    // لذا إن وُجد توكن مخزَّن نعتبره صالحًا مبدئيًا ويُعاد التحقق الفعلي عند أول طلب API
    // (أي 401 سيُعيد توجيه المستخدم لتسجيل الدخول عبر ApiClient interceptor).
    state = state.copyWith(step: hasSession ? AuthStep.authenticated : AuthStep.unauthenticated);
  }

  Future<void> sendOtp(String phone) async {
    state = state.copyWith(loading: true, error: null);
    try {
      await _repository.sendOtp(phone);
      state = state.copyWith(step: AuthStep.otpSent, phone: phone, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<void> verifyOtp(String code, {String? fullName}) async {
    if (state.phone == null) return;
    state = state.copyWith(loading: true, error: null);
    try {
      final captain = await _repository.verifyOtp(phone: state.phone!, code: code, fullName: fullName);
      state = state.copyWith(step: AuthStep.authenticated, captain: captain, loading: false);
    } on ApiException catch (e) {
      state = state.copyWith(loading: false, error: e.message);
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    state = const AuthState(step: AuthStep.unauthenticated);
  }
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(ref.watch(authRepositoryProvider));
});
