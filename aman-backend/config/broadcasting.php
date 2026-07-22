<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    'connections' => [

        // Laravel Reverb: خادم WebSocket ذاتي الاستضافة متوافق مع بروتوكول Pusher.
        // يسمح بالتشغيل محليًا/على الخادم الخاص دون الاعتماد على خدمة خارجية مدفوعة.
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'log' => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],

];
