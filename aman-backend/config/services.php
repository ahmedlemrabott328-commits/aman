<?php

return [

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials_path' => env('FCM_CREDENTIALS_PATH'), // مسار ملف Service Account JSON
        // مؤقت للتطوير فقط: توكن جاهز بدل توليده ديناميكيًا؛ استبدله بتنفيذ FcmService::getAccessToken()
        'access_token' => env('FCM_ACCESS_TOKEN'),
    ],

    'sms_gateway' => [
        'provider' => env('SMS_GATEWAY_PROVIDER', 'local'),
        'key' => env('SMS_GATEWAY_KEY'),
    ],

    'documents' => [
        // القرص المستخدَم فعليًا لوثائق الكباتن. افتراضيًا نفس FILESYSTEM_DISK العام،
        // لكن يمكن فصله (متغيّر مستقل) إن رغبت لاحقًا في قرص مختلف خاص بالوثائق فقط.
        'disk' => env('DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
        'url_ttl_minutes' => env('DOCUMENT_URL_TTL_MINUTES', 15),
    ],

];
