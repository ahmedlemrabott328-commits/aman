/// استثناء موحّد لكل أخطاء الـ API — يحوّل رسالة الخادم (بصيغتنا الموحّدة
/// { success, message, errors }) إلى رسالة قابلة للعرض مباشرة للكابتن
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}
