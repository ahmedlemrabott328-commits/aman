import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/trip.dart';
import 'trip_controller.dart';

class TripActiveScreen extends ConsumerWidget {
  const TripActiveScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(tripControllerProvider);
    final trip = state.trip;

    if (trip == null) {
      // لا رحلة نشطة (مثلاً بعد إعادة فتح التطبيق) — إعادة توجيه للرئيسية
      WidgetsBinding.instance.addPostFrameCallback((_) => context.go('/'));
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      appBar: AppBar(title: Text('رحلة ${trip.tripCode}', style: AppTheme.tabular(size: 18, weight: FontWeight.w700))),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _CustomerCard(trip: trip),
              const SizedBox(height: 16),
              _LocationCard(trip: trip),
              const Spacer(),
              if (state.error != null) ...[
                Text(state.error!, style: const TextStyle(color: AppColors.terracotta), textAlign: TextAlign.center),
                const SizedBox(height: 12),
              ],
              _ActionButton(trip: trip, loading: state.loading),
            ],
          ),
        ),
      ),
    );
  }
}

class _CustomerCard extends StatelessWidget {
  const _CustomerCard({required this.trip});
  final Trip trip;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            const CircleAvatar(radius: 24, backgroundColor: AppColors.indigo50, child: Icon(Icons.person, color: AppColors.indigo500)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(trip.customer?.fullName ?? 'الزبون', style: Theme.of(context).textTheme.titleMedium),
                  Text(trip.customer?.phone ?? '', style: AppTheme.tabular(size: 13, color: AppColors.inkSoft)),
                ],
              ),
            ),
            if (trip.customer?.phone != null)
              IconButton.filled(
                onPressed: () => launchUrl(Uri.parse('tel:${trip.customer!.phone}')),
                icon: const Icon(Icons.call_rounded),
                style: IconButton.styleFrom(backgroundColor: AppColors.teal),
              ),
          ],
        ),
      ),
    );
  }
}

class _LocationCard extends StatelessWidget {
  const _LocationCard({required this.trip});
  final Trip trip;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _addressRow(context, Icons.trip_origin, AppColors.indigo500, 'الانطلاق', trip.pickup.address),
            if (trip.dropoff != null) ...[
              const SizedBox(height: 12),
              _addressRow(context, Icons.location_on, AppColors.terracotta, 'الوجهة', trip.dropoff!.address),
            ],
            const Divider(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('السعر التقديري', style: Theme.of(context).textTheme.bodySmall),
                Text(
                  '${trip.estimatedPrice?.toStringAsFixed(0) ?? '—'} ${trip.currency}',
                  style: AppTheme.tabular(size: 16, weight: FontWeight.w700, color: AppColors.teal),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _addressRow(BuildContext context, IconData icon, Color color, String label, String address) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: Theme.of(context).textTheme.bodySmall),
              Text(address, style: Theme.of(context).textTheme.bodyMedium),
            ],
          ),
        ),
      ],
    );
  }
}

class _ActionButton extends ConsumerWidget {
  const _ActionButton({required this.trip, required this.loading});
  final Trip trip;
  final bool loading;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final controller = ref.read(tripControllerProvider.notifier);

    switch (trip.status) {
      case TripStatus.accepted:
        return ElevatedButton(
          onPressed: loading ? null : controller.markArrived,
          child: _label(loading, 'وصلت إلى موقع الزبون'),
        );
      case TripStatus.arrived:
        return ElevatedButton(
          onPressed: loading ? null : controller.startTrip,
          child: _label(loading, 'بدء الرحلة'),
        );
      case TripStatus.inProgress:
        return ElevatedButton(
          onPressed: loading ? null : () => _showCompleteDialog(context, ref),
          style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal),
          child: _label(loading, 'إنهاء الرحلة'),
        );
      case TripStatus.completed:
        return ElevatedButton(
          onPressed: () {
            controller.clear();
            context.go('/');
          },
          child: const Text('العودة للرئيسية'),
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _label(bool loading, String text) {
    if (!loading) return Text(text);
    return const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white));
  }

  Future<void> _showCompleteDialog(BuildContext context, WidgetRef ref) async {
    final distanceController = TextEditingController(text: trip.distanceKm?.toStringAsFixed(1) ?? '');
    final durationController = TextEditingController(text: trip.durationMin?.toString() ?? '');

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تأكيد بيانات الرحلة الفعلية'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: distanceController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'المسافة الفعلية (كم)'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: durationController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'المدة الفعلية (دقيقة)'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('تأكيد وإنهاء')),
        ],
      ),
    );

    if (confirmed != true) return;

    final distance = double.tryParse(distanceController.text) ?? trip.distanceKm ?? 0;
    final duration = int.tryParse(durationController.text) ?? trip.durationMin ?? 0;

    await ref.read(tripControllerProvider.notifier).completeTrip(actualDistanceKm: distance, actualDurationMin: duration);
  }
}
