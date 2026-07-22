import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/trip.dart';
import 'trip_controller.dart';

const _statusMessages = {
  TripStatus.requested: 'جارٍ إرسال طلبك...',
  TripStatus.searching: 'جارٍ البحث عن أقرب كابتن...',
  TripStatus.accepted: 'الكابتن في طريقه إليك',
  TripStatus.arrived: 'الكابتن بانتظارك الآن',
  TripStatus.inProgress: 'الرحلة جارية',
};

class TripTrackingScreen extends ConsumerWidget {
  const TripTrackingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(tripControllerProvider);
    final trip = state.trip;

    if (trip == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => context.go('/'));
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    // الانتقال التلقائي عند انتهاء الرحلة أو إلغائها
    ref.listen(tripControllerProvider, (previous, next) {
      if (next.trip?.status == TripStatus.completed) {
        context.go('/rate');
      } else if (next.trip?.status == TripStatus.cancelled || next.trip?.status == TripStatus.noCaptainFound) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(next.trip?.status == TripStatus.noCaptainFound ? 'تعذّر إيجاد كابتن متاح حاليًا' : 'أُلغيت الرحلة')),
        );
        ref.read(tripControllerProvider.notifier).clear();
        context.go('/');
      }
    });

    return Scaffold(
      body: Stack(
        children: [
          FlutterMap(
            options: MapOptions(
              initialCenter: LatLng(trip.pickup.lat, trip.pickup.lng),
              initialZoom: 14,
            ),
            children: [
              TileLayer(urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', userAgentPackageName: 'mr.aman.customer'),
              MarkerLayer(markers: [
                Marker(point: LatLng(trip.pickup.lat, trip.pickup.lng), width: 36, height: 36, child: const Icon(Icons.trip_origin, color: AppColors.indigo500)),
                if (trip.dropoff != null)
                  Marker(point: LatLng(trip.dropoff!.lat, trip.dropoff!.lng), width: 36, height: 36, child: const Icon(Icons.location_on_rounded, color: AppColors.terracotta, size: 32)),
                if (state.captainLat != null && state.captainLng != null)
                  Marker(
                    point: LatLng(state.captainLat!, state.captainLng!),
                    width: 40, height: 40,
                    child: const Icon(Icons.local_taxi_rounded, color: AppColors.gold, size: 34),
                  ),
              ]),
            ],
          ),

          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 12)]),
                child: Row(
                  children: [
                    if (trip.status == TripStatus.searching)
                      const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.gold))
                    else
                      const Icon(Icons.bolt_rounded, color: AppColors.gold),
                    const SizedBox(width: 10),
                    Expanded(child: Text(_statusMessages[trip.status] ?? '', style: Theme.of(context).textTheme.titleMedium)),
                  ],
                ),
              ),
            ),
          ),

          Positioned(left: 0, right: 0, bottom: 0, child: _BottomPanel(trip: trip)),
        ],
      ),
    );
  }
}

class _BottomPanel extends ConsumerWidget {
  const _BottomPanel({required this.trip});
  final Trip trip;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
      decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (trip.captain != null) ...[
            Row(
              children: [
                const CircleAvatar(radius: 26, backgroundColor: AppColors.indigo50, child: Icon(Icons.person, color: AppColors.indigo500)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(trip.captain!.fullName, style: Theme.of(context).textTheme.titleMedium),
                      Row(children: [
                        const Icon(Icons.star_rounded, size: 16, color: AppColors.gold),
                        Text(' ${trip.captain!.ratingAvg.toStringAsFixed(1)}', style: AppTheme.tabular(size: 13)),
                        if (trip.captain!.vehiclePlate != null) ...[
                          const Text('  •  '),
                          Text(trip.captain!.vehiclePlate!, style: AppTheme.tabular(size: 13)),
                        ],
                      ]),
                    ],
                  ),
                ),
                IconButton.filled(
                  onPressed: () => launchUrl(Uri.parse('tel:${trip.captain!.phone}')),
                  icon: const Icon(Icons.call_rounded),
                  style: IconButton.styleFrom(backgroundColor: AppColors.teal),
                ),
              ],
            ),
            const Divider(height: 28),
          ],

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('السعر التقديري', style: Theme.of(context).textTheme.bodySmall),
              Text('${trip.estimatedPrice?.toStringAsFixed(0) ?? '—'} ${trip.currency}', style: AppTheme.tabular(size: 16, weight: FontWeight.w700)),
            ],
          ),

          if (trip.status == TripStatus.requested || trip.status == TripStatus.searching || trip.status == TripStatus.accepted) ...[
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () => _confirmCancel(context, ref),
              style: OutlinedButton.styleFrom(foregroundColor: AppColors.terracotta, side: const BorderSide(color: AppColors.terracotta)),
              child: const Text('إلغاء الرحلة'),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _confirmCancel(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('إلغاء الرحلة'),
        content: const Text('هل أنت متأكد من رغبتك في إلغاء هذه الرحلة؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('تراجع')),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: AppColors.terracotta),
            child: const Text('نعم، إلغاء'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await ref.read(tripControllerProvider.notifier).cancel();
    }
  }
}
