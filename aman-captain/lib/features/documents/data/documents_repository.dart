import 'dart:io';
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class CaptainDocument {
  const CaptainDocument({
    required this.id,
    required this.documentType,
    required this.fileUrl,
    required this.status,
    this.rejectionReason,
    required this.createdAt,
  });

  final int id;
  final String documentType;
  final String? fileUrl;
  final String status; // pending | approved | rejected
  final String? rejectionReason;
  final DateTime createdAt;

  factory CaptainDocument.fromJson(Map<String, dynamic> json) => CaptainDocument(
        id: json['id'] as int,
        documentType: json['document_type'] as String,
        fileUrl: json['file_url'] as String?,
        status: json['status'] as String,
        rejectionReason: json['rejection_reason'] as String?,
        createdAt: DateTime.parse(json['created_at'] as String),
      );
}

class DocumentsRepository {
  DocumentsRepository(this._client);
  final ApiClient _client;

  Future<List<CaptainDocument>> list() {
    return _client.request(
      (dio) => dio.get('/documents'),
      (data) => (data as List).map((e) => CaptainDocument.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }

  /// رفع فعلي عبر multipart/form-data — يطابق UploadDocumentRequest في aman-backend
  /// (الحقول: document_type, file, expires_at اختياري). لا رابط وهمي بعد الآن.
  Future<CaptainDocument> upload({
    required String documentType,
    required File file,
    DateTime? expiresAt,
    void Function(int sent, int total)? onProgress,
  }) {
    return _client.request(
      (dio) => dio.post(
        '/documents',
        data: FormData.fromMap({
          'document_type': documentType,
          'file': MultipartFile.fromFileSync(file.path, filename: file.path.split('/').last),
          if (expiresAt != null) 'expires_at': expiresAt.toIso8601String().split('T').first,
        }),
        onSendProgress: onProgress,
      ),
      (data) => CaptainDocument.fromJson(data as Map<String, dynamic>),
    );
  }
}
