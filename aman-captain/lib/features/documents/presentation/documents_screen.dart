import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../data/documents_repository.dart';

final documentsRepositoryProvider = Provider<DocumentsRepository>((ref) {
  return DocumentsRepository(ref.watch(apiClientProvider));
});

final documentsListProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(documentsRepositoryProvider).list();
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

  Future<void> _pickAndUpload(String type) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 85);
    if (file == null) return;

    setState(() => _uploading.add(type));
    try {
      await ref.read(documentsRepositoryProvider).upload(documentType: type, file: File(file.path));
      ref.invalidate(documentsListProvider); // إعادة جلب القائمة لعرض الحالة الحقيقية من الخادم
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
      if (mounted) setState(() => _uploading.remove(type));
    }
  }

  @override
  Widget build(BuildContext context) {
    final documentsAsync = ref.watch(documentsListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('وثائق الحساب')),
      body: documentsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => Center(child: Text('تعذّر تحميل الوثائق', style: Theme.of(context).textTheme.bodyMedium)),
        data: (documents) => RefreshIndicator(
          onRefresh: () => ref.refresh(documentsListProvider.future),
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: _requiredDocuments.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final (type, label) = _requiredDocuments[index];
              final isUploading = _uploading.contains(type);
              // آخر وثيقة مرفوعة من هذا النوع (الخادم يحتفظ بكل محاولات الرفع تاريخيًا)
              final latest = documents.where((d) => d.documentType == type).fold<CaptainDocument?>(
                    null,
                    (latest, d) => latest == null || d.createdAt.isAfter(latest.createdAt) ? d : latest,
                  );

              return Card(
                child: ListTile(
                  leading: Icon(_statusIcon(latest?.status), color: _statusColor(latest?.status)),
                  title: Text(label),
                  subtitle: Text(_statusLabel(latest?.status, latest?.rejectionReason)),
                  trailing: isUploading
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                      : TextButton(
                          onPressed: () => _pickAndUpload(type),
                          child: Text(latest == null ? 'رفع' : 'إعادة الرفع'),
                        ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  IconData _statusIcon(String? status) {
    switch (status) {
      case 'approved': return Icons.check_circle_rounded;
      case 'rejected': return Icons.cancel_rounded;
      case 'pending': return Icons.hourglass_top_rounded;
      default: return Icons.upload_file_rounded;
    }
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'approved': return AppColors.teal;
      case 'rejected': return AppColors.terracotta;
      case 'pending': return AppColors.gold;
      default: return AppColors.indigo500;
    }
  }

  String _statusLabel(String? status, String? rejectionReason) {
    switch (status) {
      case 'approved': return 'معتمدة';
      case 'rejected': return 'مرفوضة${rejectionReason != null ? ': $rejectionReason' : ''}';
      case 'pending': return 'بانتظار المراجعة';
      default: return 'لم تُرفع بعد';
    }
  }
}
