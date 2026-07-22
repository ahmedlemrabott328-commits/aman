import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/presentation/auth_controller.dart';
import 'home_controller.dart';
import 'trip_offer_sheet.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  final MapController _mapController = MapController();
  LatLng _center = const LatLng(18.0858, -15.9785); // نواكشوط افتراضيًا حتى يُحدَّد الموقع الفعلي
  TripOffer? _sheetShownFor;

  @override
  void initState() {
    super.initState();
    _locateMe();
  }

  Future<void> _locateMe() async {
    try {
      final position = await Geolocator.getCurrentPosition();
      setState(() => _center = LatLng(position.latitude, position.longitude));
      _mapController.move(_center, 15);
    } catch (_) {
      // تجاهل بصمت: الخريطة تبقى على الموقع الافتراضي حتى تُمنح صلاحية الموقع
    }
  }

  @override
  Widget build(BuildContext context) {
    final homeState = ref.watch(homeControllerProvider);
    final captain = ref.watch(authControllerProvider).captain;

    // إظهار بطاقة العرض فور وصول عرض جديد (مرة واحدة لكل عرض)
    ref.listen(homeControllerProvider, (previous, next) {
      final offer = next.currentOffer;
      if (offer != null && _sheetShownFor?.tripId != offer.tripId) {
        _sheetShownFor = offer;
        showModalBottomSheet(
          context: context,
          isDismissible: false,
          enableDrag: false,
          builder: (_) => TripOfferSheet(offer: offer),
        ).then((accepted) {
          if (accepted == true && mounted) context.go('/trip');
        });
      }
    });

    return Scaffold(
      body: Stack(
        children: [
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(initialCenter: _center, initialZoom: 14),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'mr.aman.captain',
              ),
              MarkerLayer(markers: [
                Marker(
                  point: _center,
                  width: 44, height: 44,
                  child: const Icon(Icons.local_taxi_rounded, color: AppColors.indigo500, size: 36),
                ),
              ]),
            ],
          ),

          // شريط علوي بحالة الاتصال
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 12, offset: const Offset(0, 4))],
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 10, height: 10,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: homeState.isOnline ? AppColors.gold : AppColors.inkSoft.withValues(alpha: 0.4),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              homeState.isOnline ? 'متصل — بانتظار الطلبات' : 'غير متصل',
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                          ),
                          Switch(
                            value: homeState.isOnline,
                            activeThumbColor: AppColors.gold,
                            onChanged: homeState.togglingOnline || captain?.isApproved != true
                                ? null
                                : (value) => ref.read(homeControllerProvider.notifier).toggleOnline(value),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  _ProfileButton(onTap: () => context.push('/profile')),
                ],
              ),
            ),
          ),

          if (captain != null && !captain.isApproved)
            Positioned(
              bottom: 24, left: 16, right: 16,
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: AppColors.goldLight, borderRadius: BorderRadius.circular(14)),
                child: Row(
                  children: [
                    const Icon(Icons.hourglass_top_rounded, color: AppColors.indigo700),
                    const SizedBox(width: 10),
                    const Expanded(
                      child: Text('حسابك بانتظار اعتماد الإدارة. أكمل رفع وثائقك للإسراع في المراجعة.', style: TextStyle(fontWeight: FontWeight.w600)),
                    ),
                    TextButton(onPressed: () => context.push('/documents'), child: const Text('الوثائق')),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ProfileButton extends StatelessWidget {
  const _ProfileButton({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(24),
      child: Container(
        width: 48, height: 48,
        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
        child: const Icon(Icons.person_outline_rounded, color: AppColors.ink),
      ),
    );
  }
}
