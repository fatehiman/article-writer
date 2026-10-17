<?php

// Publish drivers ("destinations"). Each class implements App\Publishing\Publisher and
// describes its own form fields. To support a new platform, add a class and list it here.
return [
    'drivers' => [
        'webhook' => App\Publishing\Drivers\WebhookPublisher::class,
        'wordpress' => App\Publishing\Drivers\WordPressPublisher::class,
        'telegram' => App\Publishing\Drivers\TelegramPublisher::class,
        'instagram' => App\Publishing\Drivers\InstagramPublisher::class,
        'facebook' => App\Publishing\Drivers\FacebookPublisher::class,
        'linkedin' => App\Publishing\Drivers\LinkedInPublisher::class,
    ],

    // Seconds for one publish HTTP call.
    'timeout' => 60,

    // Block webhooks/targets that point to private or local addresses (SSRF guard).
    'block_private_hosts' => env('PUBLISH_BLOCK_PRIVATE_HOSTS', true),
];
