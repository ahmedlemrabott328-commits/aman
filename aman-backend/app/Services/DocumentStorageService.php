<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * نقطة مركزية واحدة للتعامل مع تخزين الملفات الحسّاسة (وثائق الكباتن).
 * أي تغيير مستقبلي لمزوّد التخزين (مثلاً الانتقال من S3 إلى قرص محلي مختلف)
 * يحدث هنا فقط دون لمس أي Controller.
 */
class DocumentStorageService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('services.documents.disk', config('filesystems.default'));
    }

    /**
     * تخزين ملف مرفوع تحت مجلد خاص بكل كابتن، باسم عشوائي غير قابل للتخمين
     * (لا نستخدم اسم الملف الأصلي أبدًا: قد يحتوي بيانات شخصية أو يسبب تعارضًا).
     */
    public function store(UploadedFile $file, int $captainId, string $documentType): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "captain-documents/{$captainId}/{$documentType}/{$filename}";

        Storage::disk($this->disk)->putFileAs(
            dirname($path), $file, basename($path), ['visibility' => 'private'],
        );

        return $path;
    }

    /**
     * رابط مؤقت وموقَّع لعرض الوثيقة (صالح لمدة محدودة فقط). القرص المحلي
     * ('local') لا يدعم temporaryUrl فعليًا، لذا نُرجع رابط route محمي بدلاً
     * منه في بيئة التطوير — راجع routes/api.php (captain.documents.preview).
     */
    public function temporaryUrl(string $path): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl(
                $path, now()->addMinutes((int) config('services.documents.url_ttl_minutes', 15)),
            );
        }

        return route('documents.preview', ['path' => base64_encode($path)]);
    }

    public function delete(string $path): void
    {
        Storage::disk($this->disk)->delete($path);
    }

    public function disk(): string
    {
        return $this->disk;
    }
}
