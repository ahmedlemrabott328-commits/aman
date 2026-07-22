<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Captain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * بديل تطويري فقط لعرض وثائق الكباتن من القرص المحلي عندما لا يكون قرص S3
 * مُفعَّلاً (راجع DocumentStorageService::temporaryUrl). في الإنتاج الفعلي
 * يجب استخدام قرص s3 دائمًا؛ هذا المسار موجود فقط ليعمل النظام كاملاً محليًا
 * دون الحاجة لحساب S3 أثناء التطوير.
 */
class DocumentPreviewController extends Controller
{
    public function show(Request $request, string $path)
    {
        $decodedPath = base64_decode($path);

        // تحقق أمني: المسار يجب أن يكون بالضبط داخل مجلد وثائق الكباتن، لمنع
        // أي محاولة قراءة ملفات عشوائية أخرى على القرص عبر تلاعب بالـ base64.
        if (! str_starts_with($decodedPath, 'captain-documents/')) {
            abort(403);
        }

        $segments = explode('/', $decodedPath);
        $ownerCaptainId = (int) ($segments[1] ?? 0);

        $user = $request->user();
        $isOwner = $user instanceof Captain && $user->id === $ownerCaptainId;
        $isAdmin = $user instanceof Admin;

        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        $disk = config('services.documents.disk', 'local');

        if (! Storage::disk($disk)->exists($decodedPath)) {
            abort(404);
        }

        return Storage::disk($disk)->response($decodedPath);
    }
}
