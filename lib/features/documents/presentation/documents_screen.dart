import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../data/documents_repository.dart';

final documentsRepositoryProvider = Provider<DocumentsRepository>((ref) {
  return DocumentsRepository(ref.watch(apiClientProvider));
});

const _requiredDocuments = [
  ('license', 'رخصة السياقة'),
  ('id_card', 'البطاقة الوطنية'),
  ('vehicle_registration', 'استمارة المركبة'),
  ('insurance', 'تأمين المركبة'),
];

class DocumentsScreen extends ConsumerStatefulWidget {
  const DocumentsScreen({super.key});

  @override
  ConsumerState<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends ConsumerState<DocumentsScreen> {
  final Set<String> _uploading = {};
  final Set<String> _uploaded = {};

  Future<void> _pickAndUpload(String type) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 85);
    if (file == null) return;

    setState(() => _uploading.add(type));
    try {
      // TODO: رفع الملف فعليًا لخدمة تخزين (S3/Local) والحصول على fileUrl الحقيقي
      // قبل الربط بـ endpoint حفظ الوثيقة. هذا مؤقت لغرض العرض فقط.
      final fakeUrl = 'https://storage.aman.mr/documents/${file.name}';
      await ref.read(documentsRepositoryProvider).upload(documentType: type, fileUrl: fakeUrl);
      setState(() => _uploaded.add(type));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تم رفع الوثيقة، بانتظار مراجعة الإدارة')),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذّر رفع الوثيقة، حاول مجددًا'), backgroundColor: AppColors.terracotta),
        );
      }
    } finally {
      setState(() => _uploading.remove(type));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('وثائق الحساب')),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _requiredDocuments.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final (type, label) = _requiredDocuments[index];
          final isUploading = _uploading.contains(type);
          final isUploaded = _uploaded.contains(type);

          return Card(
            child: ListTile(
              leading: Icon(
                isUploaded ? Icons.check_circle_rounded : Icons.upload_file_rounded,
                color: isUploaded ? AppColors.teal : AppColors.indigo500,
              ),
              title: Text(label),
              subtitle: Text(isUploaded ? 'تم الرفع — بانتظار المراجعة' : 'لم تُرفع بعد'),
              trailing: isUploading
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : TextButton(
                      onPressed: () => _pickAndUpload(type),
                      child: Text(isUploaded ? 'إعادة الرفع' : 'رفع'),
                    ),
            ),
          );
        },
      ),
    );
  }
}
