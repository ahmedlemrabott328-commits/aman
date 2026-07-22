import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_colors.dart';
import 'auth_controller.dart';

class OtpVerifyScreen extends ConsumerStatefulWidget {
  const OtpVerifyScreen({super.key});

  @override
  ConsumerState<OtpVerifyScreen> createState() => _OtpVerifyScreenState();
}

class _OtpVerifyScreenState extends ConsumerState<OtpVerifyScreen> {
  final _codeController = TextEditingController();
  final _nameController = TextEditingController();

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_codeController.text.trim().length != 4) return;
    await ref.read(authControllerProvider.notifier).verifyOtp(
          _codeController.text.trim(),
          fullName: _nameController.text.trim().isEmpty ? null : _nameController.text.trim(),
        );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 24),
              Text('أدخل رمز التحقق', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 4),
              Text('أُرسل رمز مكوّن من 4 أرقام إلى ${authState.phone ?? ''}', style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 28),
              TextField(
                controller: _codeController,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                maxLength: 4,
                style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, letterSpacing: 12),
                decoration: const InputDecoration(counterText: '', hintText: '••••'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: 'الاسم الكامل (لأول مرة فقط)'),
              ),
              if (authState.error != null) ...[
                const SizedBox(height: 12),
                Text(authState.error!, style: const TextStyle(color: AppColors.terracotta, fontSize: 13)),
              ],
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: authState.loading ? null : _submit,
                child: authState.loading
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('تأكيد وتسجيل الدخول'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
