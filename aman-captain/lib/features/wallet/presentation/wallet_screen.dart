import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart' as intl;
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../data/wallet_repository.dart';

final walletFutureProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(walletRepositoryProvider).earnings();
});

class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final walletAsync = ref.watch(walletFutureProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('المحفظة والأرباح')),
      body: walletAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('تعذّر تحميل المحفظة', style: Theme.of(context).textTheme.bodyMedium)),
        data: (wallet) => RefreshIndicator(
          onRefresh: () => ref.refresh(walletFutureProvider.future),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.indigo500,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('الرصيد الحالي', style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 14)),
                    const SizedBox(height: 6),
                    Text(
                      '${wallet.balance.toStringAsFixed(0)} ${wallet.currency}',
                      style: AppTheme.tabular(size: 32, weight: FontWeight.w800, color: wallet.balance < 0 ? AppColors.terracottaLight : Colors.white),
                    ),
                    if (wallet.balance < 0) ...[
                      const SizedBox(height: 8),
                      Text('يوجد مبلغ مستحق للمنصة — سيُخصم من أرباحك القادمة', style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12)),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 20),
              Text('سجل الحركات', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              if (wallet.transactions.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 40),
                  child: Center(child: Text('لا توجد حركات بعد', style: Theme.of(context).textTheme.bodySmall)),
                )
              else
                ...wallet.transactions.map((tx) => _TransactionTile(tx: tx)),
            ],
          ),
        ),
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.tx});
  final WalletTransaction tx;

  @override
  Widget build(BuildContext context) {
    final isPositive = tx.amount >= 0;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: isPositive ? AppColors.tealLight : AppColors.terracottaLight,
          child: Icon(isPositive ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
              color: isPositive ? AppColors.teal : AppColors.terracotta, size: 20),
        ),
        title: Text(tx.description ?? tx.label),
        subtitle: Text(intl.DateFormat('dd/MM/yyyy HH:mm', 'ar').format(tx.createdAt), style: AppTheme.tabular(size: 12, color: AppColors.inkSoft)),
        trailing: Text(
          '${isPositive ? '+' : ''}${tx.amount.toStringAsFixed(0)}',
          style: AppTheme.tabular(size: 15, weight: FontWeight.w700, color: isPositive ? AppColors.teal : AppColors.terracotta),
        ),
      ),
    );
  }
}
