import 'package:dio/dio.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

/// عميل HTTP مركزي لكل استدعاءات captain API.
/// عنوان الخادم قابل للتغيير عبر --dart-define=API_BASE_URL=... عند البناء
/// (مختلف بين بيئة التطوير والإنتاج دون تعديل الكود).
class ApiClient {
  ApiClient(this._tokenStorage) {
    _dio = Dio(
      BaseOptions(
        baseUrl: const String.fromEnvironment(
          'API_BASE_URL',
          defaultValue: 'http://10.0.2.2:8000/api/v1/captain', // 10.0.2.2 = localhost عبر محاكي أندرويد
        ),
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {'Accept': 'application/json'},
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            await _tokenStorage.clear();
            // البث خارجًا يُدار من AuthController عبر GoRouter's redirect؛ هنا فقط ننظّف التوكن
          }
          handler.next(error);
        },
      ),
    );

    assert(() {
      _dio.interceptors.add(PrettyDioLogger(requestBody: true, responseBody: true));
      return true;
    }());
  }

  late final Dio _dio;
  final TokenStorage _tokenStorage;

  Dio get dio => _dio;

  /// تنفيذ الطلب مع تحويل أخطاء Dio تلقائيًا إلى ApiException قابلة للعرض
  Future<T> request<T>(
    Future<Response<dynamic>> Function(Dio dio) call,
    T Function(dynamic data) parse,
  ) async {
    try {
      final response = await call(_dio);
      return parse(response.data['data']);
    } on DioException catch (e) {
      final message = e.response?.data is Map
          ? (e.response?.data['message'] as String?) ?? 'حدث خطأ غير متوقع'
          : _networkErrorMessage(e);
      throw ApiException(message, statusCode: e.response?.statusCode);
    }
  }

  String _networkErrorMessage(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout || e.type == DioExceptionType.receiveTimeout) {
      return 'انتهت مهلة الاتصال، تحقق من الإنترنت';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'تعذّر الاتصال بالخادم';
    }
    return 'حدث خطأ غير متوقع، حاول مجددًا';
  }
}
