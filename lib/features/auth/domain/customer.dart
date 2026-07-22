import 'package:equatable/equatable.dart';

class Customer extends Equatable {
  const Customer({
    required this.id,
    this.fullName,
    required this.phone,
    this.email,
    this.avatarUrl,
    required this.ratingAvg,
  });

  final int id;
  final String? fullName;
  final String phone;
  final String? email;
  final String? avatarUrl;
  final double ratingAvg;

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      id: json['id'] as int,
      fullName: json['full_name'] as String?,
      phone: json['phone'] as String,
      email: json['email'] as String?,
      avatarUrl: json['avatar_url'] as String?,
      ratingAvg: (json['rating_avg'] as num?)?.toDouble() ?? 5.0,
    );
  }

  @override
  List<Object?> get props => [id, fullName, phone];
}
