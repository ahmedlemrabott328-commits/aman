import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../data/auth_repository.dart';
import '../domain/customer.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(apiClientProvider), ref.watch(tokenStorageProvider));
});

enum AuthStep { unknown, unauthenticated, otpSent, authenticated }

class AuthState {
  const AuthState({this.step = AuthStep.unknown, this.phone, this.customer, this.loading = false, this.error});

  final AuthStep step;
  final String? phone;
  final Customer? customer;
  final bool loading;
  final String? error;

  AuthState copyWith({AuthStep? step, String? phone, Customer? customer, bool? loading, String? error}) {
    return AuthState(
      step: step ?? this.step,
      phone: phone ?? this.phone,
      customer: customer ?? this.customer,
      loading: loading ?? this.loading,
      error: error,
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
      final customer = await _repository.verifyOtp(phone: state.phone!, code: code, fullName: fullName);
      state = state.copyWith(step: AuthStep.authenticated, customer: customer, loading: false);
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
