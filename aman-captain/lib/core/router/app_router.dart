import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/phone_entry_screen.dart';
import '../../features/documents/presentation/documents_screen.dart';
import '../../features/history/presentation/trip_history_screen.dart';
import '../../features/home/presentation/home_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/trip/presentation/trip_active_screen.dart';
import '../../features/wallet/presentation/wallet_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      final authState = ref.read(authControllerProvider);
      final isAuthenticated = authState.step == AuthStep.authenticated;
      final isLoginRoute = state.matchedLocation == '/login';

      if (authState.step == AuthStep.unknown) return null; // بانتظار استرجاع الجلسة
      if (!isAuthenticated && !isLoginRoute) return '/login';
      if (isAuthenticated && isLoginRoute) return '/';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const PhoneEntryScreen()),
      GoRoute(path: '/', builder: (context, state) => const HomeScreen()),
      GoRoute(path: '/trip', builder: (context, state) => const TripActiveScreen()),
      GoRoute(path: '/documents', builder: (context, state) => const DocumentsScreen()),
      GoRoute(path: '/wallet', builder: (context, state) => const WalletScreen()),
      GoRoute(path: '/history', builder: (context, state) => const TripHistoryScreen()),
      GoRoute(path: '/profile', builder: (context, state) => const ProfileScreen()),
    ],
  );
});

/// يجسّر تغييرات حالة Riverpod (AuthState) إلى GoRouter لإعادة تقييم redirect
/// تلقائيًا عند تسجيل الدخول/الخروج، دون الحاجة لإعادة بناء التطبيق يدويًا.
class _AuthListenable extends ChangeNotifier {
  _AuthListenable(this._ref) {
    _ref.listen(authControllerProvider, (previous, next) {
      if (previous?.step != next.step) notifyListeners();
    });
  }
  // ignore: unused_field
  final Ref _ref;
}
