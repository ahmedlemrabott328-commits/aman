import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../trip/domain/trip.dart';
import '../../trip/presentation/trip_controller.dart';
import 'home_controller.dart';

/// بطاقة سفلية تظهر فور وصول عرض رحلة جديد (trip.offer.new عبر البث اللحظي)
/// مع عدّاد تنازلي مطابق لمهلة الخادم (offer_timeout_seconds)، تمنحه شعورًا
/// بإلحاح العرض قبل انتقاله تلقائيًا لكابتن آخر.
class TripOfferSheet extends ConsumerStatefulWidget {
  const TripOfferSheet({super.key, required this.offer});

  final TripOffer offer;

  @override
  ConsumerState<TripOfferSheet> createState() => _TripOfferSheetState();
}

class _TripOfferSheetState extends ConsumerState<TripOfferSheet> {
  late int _remainingSeconds = widget.offer.offerTimeoutSeconds;
  Timer? _timer;
  bool _responding = false;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds <= 1) {
        timer.cancel();
        if (mounted) Navigator.of(context).pop();
      } else {
        setState(() => _remainingSeconds--);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _accept() async {
    setState(() => _responding = true);
    final success = await ref.read(tripControllerProvider.notifier).accept(widget.offer.tripId);
    if (!mounted) return;
    ref.read(homeControllerProvider.notifier).clearOffer();
    Navigator.of(context).pop(success);
  }

  Future<void> _reject() async {
    await ref.read(tripControllerProvider.notifier).reject(widget.offer.tripId);
    if (!mounted) return;
    ref.read(homeControllerProvider.notifier).clearOffer();
    Navigator.of(context).pop(false);
  }

  @override
  Widget build(BuildContext context) {
    final progress = _remainingSeconds / widget.offer.offerTimeoutSeconds;

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.sandDeep, borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 16),

          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 6,
              backgroundColor: AppColors.sandDim,
              valueColor: AlwaysStoppedAnimation(progress > 0.3 ? AppColors.gold : AppColors.terracotta),
            ),
          ),
          const SizedBox(height: 16),

          Row(
            children: [
              const Icon(Icons.bolt_rounded, color: AppColors.gold, size: 22),
              const SizedBox(width: 6),
              Text('طلب رحلة جديد', style: Theme.of(context).textTheme.titleLarge),
              const Spacer(),
              Text('$_remainingSecondsث', style: AppTheme.tabular(size: 18, weight: FontWeight.w700, color: AppColors.terracotta)),
            ],
          ),
          const SizedBox(height: 14),

          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.location_on_outlined, color: AppColors.indigo500, size: 20),
              const SizedBox(width: 8),
              Expanded(child: Text(widget.offer.pickupAddress, style: Theme.of(context).textTheme.bodyMedium)),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.payments_outlined, color: AppColors.teal, size: 20),
              const SizedBox(width: 8),
              Text(
                '${widget.offer.estimatedPrice.toStringAsFixed(0)} ${widget.offer.currency}',
                style: AppTheme.tabular(size: 18, weight: FontWeight.w700, color: AppColors.teal),
              ),
            ],
          ),
          const SizedBox(height: 24),

          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _responding ? null : _reject,
                  child: const Text('رفض'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _responding ? null : _accept,
                  child: _responding
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('قبول الرحلة'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
