import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../trip/domain/trip.dart';
import '../../trip/presentation/trip_controller.dart';

final tripHistoryProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(tripRepositoryProvider).history();
});

const _statusLabels = {
  TripStatus.completed: 'مكتملة',
  TripStatus.cancelled: 'ملغاة',
  TripStatus.noCaptainFound: 'لم يُعثر على كابتن',
};

class TripHistoryScreen extends ConsumerWidget {
  const TripHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(tripHistoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('سجل الرحلات')),
      body: historyAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => Center(child: Text('تعذّر تحميل السجل', style: Theme.of(context).textTheme.bodyMedium)),
        data: (trips) {
          if (trips.isEmpty) {
            return Center(child: Text('لم تقم بأي رحلة بعد', style: Theme.of(context).textTheme.bodyMedium));
          }
          return RefreshIndicator(
            onRefresh: () => ref.refresh(tripHistoryProvider.future),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: trips.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) => _TripTile(trip: trips[index]),
            ),
          );
        },
      ),
    );
  }
}

class _TripTile extends StatelessWidget {
  const _TripTile({required this.trip});
  final Trip trip;

  @override
  Widget build(BuildContext context) {
    final isCompleted = trip.status == TripStatus.completed;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(trip.tripCode, style: AppTheme.tabular(size: 13, weight: FontWeight.w700, color: AppColors.inkSoft)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: isCompleted ? AppColors.tealLight : AppColors.terracottaLight,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _statusLabels[trip.status] ?? trip.status.name,
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: isCompleted ? AppColors.teal : AppColors.terracotta),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(trip.dropoff?.address ?? trip.pickup.address, maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodyMedium),
            const SizedBox(height: 6),
            Text(
              '${trip.finalPrice?.toStringAsFixed(0) ?? trip.estimatedPrice?.toStringAsFixed(0) ?? '—'} ${trip.currency}',
              style: AppTheme.tabular(size: 15, weight: FontWeight.w700),
            ),
          ],
        ),
      ),
    );
  }
}
