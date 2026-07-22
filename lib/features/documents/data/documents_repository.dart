import '../../../core/network/api_client.dart';

class DocumentsRepository {
  DocumentsRepository(this._client);
  final ApiClient _client;

  /// رفع وثيقة: في هذا الإصدار نفترض أن file_url جاهز بعد رفع الملف لخدمة تخزين
  /// خارجية (S3 مثلاً) عبر شاشة رفع منفصلة؛ endpoint التخزين الفعلي غير مُنفَّذ
  /// بعد في aman-backend (موثَّق في README كخطوة لاحقة).
  Future<void> upload({required String documentType, required String fileUrl}) {
    return _client.request(
      (dio) => dio.post('/documents', data: {'document_type': documentType, 'file_url': fileUrl}),
      (_) => null,
    );
  }
}
