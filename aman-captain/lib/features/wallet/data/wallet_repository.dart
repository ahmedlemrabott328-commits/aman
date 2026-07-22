import 'package:equatable/equatable.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers.dart';
import '../../../core/network/api_client.dart';

class WalletTransaction extends Equatable {
  const WalletTransaction({
    required this.id,
    this.tripId,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    this.description,
    required this.createdAt,
  });

  final int id;
  final int? tripId;
  final String type;
  final double amount;
  final double balanceAfter;
  final String? description;
  final DateTime createdAt;

  factory WalletTransaction.fromJson(Map<String, dynamic> json) => WalletTransaction(
        id: json['id'] as int,
        tripId: json['trip_id'] as int?,
        type: json['type'] as String,
        amount: (json['amount'] as num).toDouble(),
        balanceAfter: (json['balance_after'] as num).toDouble(),
        description: json['description'] as String?,
        createdAt: DateTime.parse(json['created_at'] as String),
      );

  String get label {
    switch (type) {
      case 'trip_earning': return 'أرباح رحلة';
      case 'commission': return 'عمولة المنصة';
      case 'adjustment': return 'تعديل إداري';
      case 'payout': return 'سحب رصيد';
      case 'bonus': return 'مكافأة';
      case 'penalty': return 'خصم';
      default: return type;
    }
  }

  @override
  List<Object?> get props => [id];
}

class WalletSummary extends Equatable {
  const WalletSummary({required this.balance, required this.currency, required this.transactions});

  final double balance;
  final String currency;
  final List<WalletTransaction> transactions;

  factory WalletSummary.fromJson(Map<String, dynamic> json) {
    final transactionsJson = (json['transactions']?['data'] as List?) ?? [];
    return WalletSummary(
      balance: (json['balance'] as num).toDouble(),
      currency: json['currency'] as String,
      transactions: transactionsJson.map((e) => WalletTransaction.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }

  @override
  List<Object?> get props => [balance];
}

class WalletRepository {
  WalletRepository(this._client);
  final ApiClient _client;

  Future<WalletSummary> earnings() {
    return _client.request(
      (dio) => dio.get('/earnings'),
      (data) => WalletSummary.fromJson(data as Map<String, dynamic>),
    );
  }
}

final walletRepositoryProvider = Provider<WalletRepository>((ref) {
  return WalletRepository(ref.watch(apiClientProvider));
});
