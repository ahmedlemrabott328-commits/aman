import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/presentation/auth_controller.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final customer = ref.watch(authControllerProvider).customer;

    return Scaffold(
      appBar: AppBar(title: const Text('الملف الشخصي')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  const CircleAvatar(radius: 32, backgroundColor: AppColors.indigo50, child: Icon(Icons.person, size: 32, color: AppColors.indigo500)),
                  const SizedBox(height: 12),
                  Text(customer?.fullName ?? 'زبون AMAN', style: Theme.of(context).textTheme.titleLarge),
                  Text(customer?.phone ?? '', style: AppTheme.tabular(size: 14, color: AppColors.inkSoft)),
                  if (customer != null) ...[
                    const SizedBox(height: 6),
                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                      const Icon(Icons.star_rounded, size: 16, color: AppColors.gold),
                      Text(' ${customer.ratingAvg.toStringAsFixed(1)}', style: AppTheme.tabular(size: 13)),
                    ]),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _MenuTile(icon: Icons.history_rounded, label: 'سجل الرحلات', onTap: () => context.push('/history')),
          _MenuTile(icon: Icons.support_agent_rounded, label: 'الدعم والمساعدة', onTap: () {}),
          const SizedBox(height: 16),
          _MenuTile(
            icon: Icons.logout_rounded,
            label: 'تسجيل الخروج',
            color: AppColors.terracotta,
            onTap: () async {
              await ref.read(authControllerProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({required this.icon, required this.label, required this.onTap, this.color});
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(icon, color: color ?? AppColors.ink),
        title: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600)),
        trailing: color == null ? const Icon(Icons.arrow_back_ios_new_rounded, size: 16) : null,
        onTap: onTap,
      ),
    );
  }
}
