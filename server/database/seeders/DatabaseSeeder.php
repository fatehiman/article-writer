<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use App\Models\AiProvider;
use App\Models\ImageSize;
use App\Models\Tone;
use Illuminate\Database\Seeder;

/**
 * Default data. Safe to run again: each part runs only when its table is empty.
 * API keys and the admin password are NOT here (public repo) - see README.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (ImageSize::query()->doesntExist()) {
            $sizes = [
                ['Small', 'general', 256, 256], ['Medium', 'general', 800, 600], ['Large', 'general', 1024, 1024],
                ['Wide (16:9)', 'general', 1280, 720],
                ['Instagram square', 'instagram', 1080, 1080], ['Instagram portrait', 'instagram', 1080, 1350],
                ['Instagram landscape', 'instagram', 1080, 566], ['Instagram story', 'instagram', 1080, 1920],
            ];
            foreach ($sizes as $i => [$name, $group, $w, $h]) {
                ImageSize::query()->create(['name' => $name, 'group' => $group, 'width' => $w, 'height' => $h, 'sort' => $i]);
            }
        }

        if (Tone::query()->doesntExist()) {
            $tones = [
                ['Professional', 'حرفه‌ای', 'Clear, confident and factual.'],
                ['Friendly', 'دوستانه', 'Warm and simple, speak to the reader as "you".'],
                ['Fun', 'سرگرم‌کننده', 'Light, playful, with small jokes, but still useful.'],
                ['Slang', 'محاوره‌ای', 'Casual everyday spoken language and common slang.'],
                ['Literary', 'ادبی', 'Rich, literary language with careful style.'],
                ['Formal', 'رسمی', 'Formal and polite, no slang.'],
                ['Persuasive', 'متقاعدکننده', 'Convince the reader, with a clear call to action at the end.'],
            ];
            foreach ($tones as $i => [$name, $fa, $hint]) {
                Tone::query()->create(['name' => $name, 'labels' => ['fa' => $fa], 'instruction' => $hint, 'sort' => $i]);
            }
        }

        if (AiProvider::query()->doesntExist()) {
            $deepseek = AiProvider::query()->create(['name' => 'DeepSeek', 'driver' => 'openai_compatible', 'base_url' => 'https://api.deepseek.com']);
            $silicon = AiProvider::query()->create(['name' => 'SiliconFlow', 'driver' => 'siliconflow_image', 'base_url' => 'https://api.siliconflow.com/v1']);

            AiAgent::query()->create([
                'ai_provider_id' => $deepseek->id, 'name' => 'DeepSeek Flash', 'kind' => 'text', 'model' => 'deepseek-flash',
                'description' => 'Fast and cheap writer.', 'options' => ['max_tokens' => 16000],
                'price' => 0, 'price_unit' => 'per_1k_words', 'sort' => 1,
            ]);
            AiAgent::query()->create([
                'ai_provider_id' => $silicon->id, 'name' => 'Qwen Image', 'kind' => 'image', 'model' => 'Qwen/Qwen-Image',
                'description' => 'High quality images.',
                'options' => ['sizes' => ['1328x1328', '1664x928', '928x1664', '1472x1140', '1140x1472', '1584x1056', '1056x1584']],
                'price' => 0, 'price_unit' => 'per_image', 'sort' => 1,
            ]);
            AiAgent::query()->create([
                'ai_provider_id' => $silicon->id, 'name' => 'FLUX.1 schnell', 'kind' => 'image', 'model' => 'black-forest-labs/FLUX.1-schnell',
                'description' => 'Fast, cheaper images.',
                'options' => ['sizes' => ['1024x1024', '1024x576', '576x1024', '1024x768', '768x1024', '960x1280', '720x1280']],
                'price' => 0, 'price_unit' => 'per_image', 'sort' => 2,
            ]);
        }
    }
}
