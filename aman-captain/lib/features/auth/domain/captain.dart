import 'package:equatable/equatable.dart';

enum ApprovalStatus { pending, approved, rejected, suspended }

ApprovalStatus approvalStatusFromString(String value) {
  return ApprovalStatus.values.firstWhere(
    (e) => e.name == value,
    orElse: () => ApprovalStatus.pending,
  );
}

class Captain extends Equatable {
  const Captain({
    required this.id,
    required this.fullName,
    required this.phone,
    required this.approvalStatus,
  });

  final int id;
  final String fullName;
  final String phone;
  final ApprovalStatus approvalStatus;

  factory Captain.fromJson(Map<String, dynamic> json) {
    return Captain(
      id: json['id'] as int,
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      approvalStatus: approvalStatusFromString(json['approval_status'] as String),
    );
  }

  bool get isApproved => approvalStatus == ApprovalStatus.approved;

  @override
  List<Object?> get props => [id, fullName, phone, approvalStatus];
}
