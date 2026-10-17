<?php

// AI drivers. A provider (admin page "AI providers") uses one driver; agents on that
// provider say which model to use. To add a vendor, write a class in app/Ai/Drivers
// and add it here - nothing else changes.
return [
    'drivers' => [
        'openai_compatible' => [
            'label' => 'OpenAI-compatible chat (DeepSeek, OpenAI, OpenRouter, SiliconFlow...)',
            'kinds' => ['text'],
            'class' => App\Ai\Drivers\OpenAiCompatibleText::class,
            'base_url' => 'https://api.deepseek.com',
        ],
        'anthropic' => [
            'label' => 'Anthropic Claude (Messages API)',
            'kinds' => ['text'],
            'class' => App\Ai\Drivers\AnthropicText::class,
            'base_url' => 'https://api.anthropic.com',
        ],
        'siliconflow_image' => [
            'label' => 'SiliconFlow images (Qwen-Image, FLUX...)',
            'kinds' => ['image'],
            'class' => App\Ai\Drivers\SiliconFlowImage::class,
            'base_url' => 'https://api.siliconflow.com/v1',
        ],
        'openai_image' => [
            'label' => 'OpenAI images (gpt-image)',
            'kinds' => ['image'],
            'class' => App\Ai\Drivers\OpenAiImage::class,
            'base_url' => 'https://api.openai.com/v1',
        ],
    ],

    'kinds' => ['text', 'image', 'video'],

    'price_units' => [
        'per_1k_words' => ['text'],
        'per_request' => ['text', 'image', 'video'],
        'per_image' => ['image'],
    ],

    // Seconds for one AI call.
    'timeout' => 300,
];
