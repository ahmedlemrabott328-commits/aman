import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import 'trip_controller.dart';

class RatingScreen extends ConsumerStatefulWidget {
  const RatingScreen({super.key});

  @override
  ConsumerState<RatingScreen> createState() => _RatingScreenState();
}

class _RatingScreenState extends ConsumerState<RatingScreen> {
  int _score = 5;
  final _commentController = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    final success = await ref.read(tripControllerProvider.notifier).rate(
          score: _score,
          comment: _commentController.text.trim().isEmpty ? null : _commentController.text.trim(),
        );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (success) _finish();
  }

  void _finish() {
    ref.read(tripControllerProvider.notifier).clear();
    context.go('/');
  }

  @override
  Widget build(BuildContext context) {
    final trip = ref.watch(tripControllerProvider).trip;

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              const Icon(Icons.check_circle_rounded, size: 64, color: AppColors.teal),
              const SizedBox(height: 16),
              Text('اكتملت رحلتك', textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineSmall),
              if (trip?.finalPrice != null) ...[
                const SizedBox(height: 4),
                Text(
                  'المبلغ المستحق: ${trip!.finalPrice!.toStringAsFixed(0)} ${trip.currency}',
                  textAlign: TextAlign.center,
                  style: AppTheme.tabular(size: 16, weight: FontWeight.w700, color: AppColors.inkSoft),
                ),
              ],
              const SizedBox(height: 28),
              Text('كيف كانت رحلتك مع الكابتن؟', textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (index) {
                  final starValue = index + 1;
                  return IconButton(
                    iconSize: 36,
                    onPressed: () => setState(() => _score = starValue),
                    icon: Icon(
                      starValue <= _score ? Icons.star_rounded : Icons.star_outline_rounded,
                      color: AppColors.gold,
                    ),
                  );
                }),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _commentController,
                maxLines: 3,
                decoration: const InputDecoration(hintText: 'أضف تعليقًا (اختياري)...'),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('إرسال التقييم'),
              ),
              const SizedBox(height: 8),
              TextButton(onPressed: _finish, child: const Text('تخطّي')),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }
}
