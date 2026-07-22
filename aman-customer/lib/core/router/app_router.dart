import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/phone_entry_screen.dart';
import '../../features/history/presentation/trip_history_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/request/presentation/home_screen.dart';
import '../../features/trip/presentation/rating_screen.dart';
import '../../features/trip/presentation/trip_tracking_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      final authState = ref.read(authControllerProvider);
      final isAuthenticated = authState.step == AuthStep.authenticated;
      final isLoginRoute = state.matchedLocation == '/login';

      if (authState.step == AuthStep.unknown) return null;
      if (!isAuthenticated && !isLoginRoute) return '/login';
      if (isAuthenticated && isLoginRoute) return '/';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const PhoneEntryScreen()),
      GoRoute(path: '/', builder: (context, state) => const HomeScreen()),
      GoRoute(path: '/trip', builder: (context, state) => const TripTrackingScreen()),
      GoRoute(path: '/rate', builder: (context, state) => const RatingScreen()),
      GoRoute(path: '/history', builder: (context, state) => const TripHistoryScreen()),
      GoRoute(path: '/profile', builder: (context, state) => const ProfileScreen()),
    ],
  );
});

class _AuthListenable extends ChangeNotifier {
  _AuthListenable(this._ref) {
    _ref.listen(authControllerProvider, (previous, next) {
      if (previous?.step != next.step) notifyListeners();
    });
  }
  // ignore: unused_field
  final Ref _ref;
}
