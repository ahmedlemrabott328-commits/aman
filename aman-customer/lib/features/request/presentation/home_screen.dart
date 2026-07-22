import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../trip/presentation/trip_controller.dart';
import 'destination_search_screen.dart';
import 'request_controller.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  final MapController _mapController = MapController();
  LatLng _center = const LatLng(18.0858, -15.9785); // نواكشوط افتراضيًا

  @override
  void initState() {
    super.initState();
    _locateMe();
    // استرجاع أي رحلة جارية بالفعل عند فتح التطبيق (بعد إغلاقه أثناء رحلة نشطة مثلاً)
    Future.microtask(() async {
      await ref.read(tripControllerProvider.notifier).restoreCurrentTrip();
      final trip = ref.read(tripControllerProvider).trip;
      if (trip != null && mounted) context.go('/trip');
    });
  }

  Future<void> _locateMe() async {
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      final position = await Geolocator.getCurrentPosition();
      setState(() => _center = LatLng(position.latitude, position.longitude));
      _mapController.move(_center, 15);
      ref.read(requestControllerProvider.notifier).setPickup('موقعي الحالي', LatLngPoint(position.latitude, position.longitude));
    } catch (_) {
      // تجاهل بصمت: الخريطة تبقى على الموقع الافتراضي
    }
  }

  Future<void> _openDestinationSearch() async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const DestinationSearchScreen()));
  }

  @override
  Widget build(BuildContext context) {
    final requestState = ref.watch(requestControllerProvider);

    return Scaffold(
      body: Stack(
        children: [
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(initialCenter: _center, initialZoom: 14),
            children: [
              TileLayer(urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', userAgentPackageName: 'mr.aman.customer'),
              MarkerLayer(markers: [
                Marker(point: _center, width: 40, height: 40, child: const Icon(Icons.my_location_rounded, color: AppColors.indigo500, size: 32)),
                if (requestState.dropoff != null)
                  Marker(
                    point: LatLng(requestState.dropoff!.lat, requestState.dropoff!.lng),
                    width: 40, height: 40,
                    child: const Icon(Icons.location_on_rounded, color: AppColors.terracotta, size: 36),
                  ),
              ]),
            ],
          ),

          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  _RoundIconButton(icon: Icons.person_outline_rounded, onTap: () => context.push('/profile')),
                ],
              ),
            ),
          ),

          Positioned(
            left: 0, right: 0, bottom: 0,
            child: _RequestCard(onPickDestination: _openDestinationSearch),
          ),
        ],
      ),
    );
  }
}

class _RequestCard extends ConsumerWidget {
  const _RequestCard({required this.onPickDestination});
  final VoidCallback onPickDestination;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(requestControllerProvider);

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InkWell(
            onTap: onPickDestination,
            borderRadius: BorderRadius.circular(14),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              decoration: BoxDecoration(color: AppColors.sand, borderRadius: BorderRadius.circular(14)),
              child: Row(
                children: [
                  const Icon(Icons.search_rounded, color: AppColors.inkSoft),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      state.dropoffAddress ?? 'إلى أين تريد الذهاب؟',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: state.dropoffAddress != null ? AppColors.ink : AppColors.inkSoft,
                            fontWeight: state.dropoffAddress != null ? FontWeight.w600 : FontWeight.normal,
                          ),
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ),

          if (state.dropoffAddress != null) ...[
            const SizedBox(height: 16),
            SizedBox(
              height: 40,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: kServices.length,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final service = kServices[index];
                  final selected = service.code == state.selectedService.code;
                  return ChoiceChip(
                    label: Text(service.label),
                    selected: selected,
                    onSelected: (_) => ref.read(requestControllerProvider.notifier).setService(service),
                    selectedColor: AppColors.indigo500,
                    labelStyle: TextStyle(color: selected ? Colors.white : AppColors.ink, fontWeight: FontWeight.w600),
                    backgroundColor: AppColors.sand,
                    side: BorderSide.none,
                  );
                },
              ),
            ),
            const SizedBox(height: 16),

            if (state.loadingEstimate)
              const Center(child: Padding(padding: EdgeInsets.symmetric(vertical: 8), child: CircularProgressIndicator(strokeWidth: 2)))
            else if (state.estimatedPrice != null)
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('السعر التقديري', style: Theme.of(context).textTheme.bodySmall),
                  Text(
                    '${state.estimatedPrice!.toStringAsFixed(0)} ${state.currency}',
                    style: AppTheme.tabular(size: 20, weight: FontWeight.w800, color: AppColors.teal),
                  ),
                ],
              ),

            if (state.error != null) ...[
              const SizedBox(height: 8),
              Text(state.error!, style: const TextStyle(color: AppColors.terracotta, fontSize: 13)),
            ],

            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: state.canRequest && !state.requesting ? () => _submit(context, ref) : null,
              child: state.requesting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('اطلب الرحلة الآن'),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _submit(BuildContext context, WidgetRef ref) async {
    final trip = await ref.read(requestControllerProvider.notifier).submit();
    if (trip == null || !context.mounted) return;
    ref.read(tripControllerProvider.notifier).setTrip(trip);
    ref.read(requestControllerProvider.notifier).reset();
    context.go('/trip');
  }
}

class _RoundIconButton extends StatelessWidget {
  const _RoundIconButton({required this.icon, required this.onTap});
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(24),
      child: Container(
        width: 48, height: 48,
        decoration: BoxDecoration(
          color: Colors.white, shape: BoxShape.circle,
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 12, offset: const Offset(0, 4))],
        ),
        child: Icon(icon, color: AppColors.ink),
      ),
    );
  }
}
