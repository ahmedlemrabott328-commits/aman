<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * إرسال إشعارات Push عبر Firebase Cloud Messaging (HTTP v1 API).
 * يحتاج عند التشغيل الفعلي: ملف اعتماد Service Account (FIREBASE_CREDENTIALS_PATH)
 * وتوليد OAuth access token عبره (عبر google/auth أو حزمة kreait/firebase-php الموصى بها).
 * تم تبسيط توليد التوكن هنا؛ في الإنتاج استبدل getAccessToken() بتنفيذ حقيقي.
 */
class FcmService
{
    private string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id', '');
    }

    /**
     * إرسال إشعار لجهاز واحد عبر رمز FCM.
     * $data: بيانات إضافية (trip_id, type...) تُستخدم من التطبيق للتنقل الصحيح عند الضغط على الإشعار.
     */
    public function sendToToken(?string $token, string $title, string $body, array $data = []): bool
    {
        if (empty($token) || empty($this->projectId)) {
            Log::info('FCM skipped: missing token or project_id', compact('token'));

            return false;
        }

        try {
            $response = Http::withToken($this->getAccessToken())
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                        'android' => ['priority' => 'high'],
                        'apns' => ['headers' => ['apns-priority' => '10']],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('FCM send failed', ['status' => $response->status(), 'body' => $response->body()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('FCM send exception: ' . $e->getMessage());

            return false;
        }
    }

    private function getAccessToken(): string
    {
        // TODO: تنفيذ فعلي عبر google/auth مع ملف Service Account JSON:
        // $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
        //     'https://www.googleapis.com/auth/firebase.messaging',
        //     config('services.fcm.credentials_path')
        // );
        // return $credentials->fetchAuthToken()['access_token'];
        return config('services.fcm.access_token', '');
    }
}
