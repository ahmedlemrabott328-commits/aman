<?php

return [

    /*
    |--------------------------------------------------------------------------
    | القرص الافتراضي
    |--------------------------------------------------------------------------
    | 'local' للتطوير المحلي بلا اعتماديات خارجية، 's3' للإنتاج (وثائق الكباتن
    | حساسة ويجب ألا تبقى على قرص محلي غير قابل للتوسع أو النسخ الاحتياطي بسهولة).
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        // قرص عام (لأصول غير حساسة فقط، مثل صور واجهات عامة إن وُجدت مستقبلاً)
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // القرص الفعلي لوثائق الكباتن في الإنتاج. متوافق مع أي مزوّد S3-compatible
        // (AWS S3, DigitalOcean Spaces, MinIO ذاتي الاستضافة...) عبر AWS_ENDPOINT
        // و AWS_USE_PATH_STYLE_ENDPOINT، وهو مهم لخيارات الاستضافة المحلية بموريتانيا
        // إن لم تتوفر خدمة AWS S3 مباشرة في المنطقة.
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private', // إلزامي: وثائق هوية وسجلات قيادة، لا تُنشر علنًا أبدًا
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
