<?php

namespace App\Generation;

use App\Ai\AiException;
use App\Ai\AiManager;
use App\Ai\ImageProcessor;
use App\Models\AiAgent;
use App\Models\Article;
use App\Models\ImageSize;
use App\Models\Setting;
use App\Publishing\DeliveryDispatcher;
use App\Support\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Writes the text and draws the images of one pending article, then sends it out. */
class ArticleGenerator
{
    public function __construct(private AiManager $ai, private DeliveryDispatcher $dispatcher)
    {
    }

    public function run(Article $article): void
    {
        if ($article->status !== Article::PENDING) {
            return;
        }
        $article->update(['status' => Article::GENERATING, 'error' => null]);

        try {
            $this->generate($article);
        } catch (Throwable $e) {
            Log::warning('Article '.$article->id.' failed: '.$e->getMessage());
            $article->update(['status' => Article::FAILED, 'error' => mb_substr($e->getMessage(), 0, 2000)]);

            return;
        }

        $this->dispatcher->queueFor($article->fresh());
    }

    private function generate(Article $article): void
    {
        $service = $article->service()->with(['tone', 'textAgent.provider', 'imageAgent.provider', 'user'])->firstOrFail();
        $user = $service->user;
        $textAgent = $service->textAgent ?? throw new AiException('The service has no text AI agent.');
        $sizes = $this->sizes($service->imageSizeIds());
        $imageAgent = $sizes ? ($service->imageAgent ?? throw new AiException('The service has no image AI agent.')) : null;

        $estimate = $this->textCost($textAgent, $service->word_count) + ($imageAgent ? $this->imageCost($imageAgent, count($sizes)) : 0);
        if ($estimate > 0 && Setting::get('low_balance_block') === '1' && (float) $user->wallet_balance < $estimate) {
            throw new AiException('Not enough money in the wallet (needs about '.money($estimate).').');
        }

        $data = $this->writeText($article, $service, $textAgent, count($sizes));
        $prompts = $data['_prompts'];
        $alts = $data['_alts'];
        unset($data['_prompts'], $data['_alts']);
        $article->update($data);

        // Old images of an earlier failed try of this article.
        foreach ($article->images as $old) {
            if ($old->path) {
                Storage::disk('local')->delete($old->path);
            }
            $old->delete();
        }

        $made = 0;
        foreach ($sizes as $i => $size) {
            $prompt = ($prompts[$i] ?? '') ?: ($prompts[0] ?? '') ?: (string) $article->title;
            $made += $this->drawImage($article, $imageAgent, $size, $i + 1, $prompt, ($alts[$i] ?? '') ?: (string) $article->title) ? 1 : 0;
        }

        $cost = round($this->textCost($textAgent, $article->word_count) + ($imageAgent ? $this->imageCost($imageAgent, $made) : 0), 4);
        if ($cost > 0) {
            Wallet::change($user, -$cost, 'charge', 'Article: '.Str::limit((string) $article->title, 120), $article);
        }

        $failedImages = count($sizes) - $made;
        $article->update([
            'status' => Article::READY,
            'cost' => $cost,
            'generated_at' => now(),
            'error' => $failedImages > 0 ? "{$failedImages} image(s) could not be made." : null,
        ]);
    }

    /** @return list<ImageSize> in the order of the service */
    private function sizes(array $ids): array
    {
        $rows = ImageSize::query()->whereIn('id', $ids)->get()->keyBy('id');

        return array_values(array_filter(array_map(fn ($id) => $rows->get($id), $ids)));
    }

    private function writeText(Article $article, $service, AiAgent $agent, int $imageCount): array
    {
        $language = config('languages.articles.'.$service->language.'.0', $service->language);
        $categories = array_values(array_filter((array) $service->categories));
        $words = max(100, (int) $service->word_count);
        $tone = $service->tone;

        $system = "You are a senior professional writer and SEO editor. You write original, accurate, well-structured articles. "
            ."You always answer with one valid JSON object and nothing else.";

        $lines = [
            "Write an article in {$language}.",
            "Base it on this title: \"{$article->source_title}\". You may polish the title, but keep its meaning and keep it in {$language}.",
            'Length: about '.$words.' words (between '.(int) ($words * 0.9).' and '.(int) ($words * 1.15).' words) in the body.',
        ];
        if ($tone) {
            $lines[] = "Tone of voice: {$tone->name}.".($tone->instruction ? ' '.$tone->instruction : '');
        }
        if ($categories) {
            $lines[] = 'Choose exactly ONE category that fits best from this list, and copy it exactly: '
                .json_encode($categories, JSON_UNESCAPED_UNICODE).'.';
        }
        if (trim((string) $service->instructions) !== '') {
            $lines[] = "Extra instructions from the customer:\n".trim($service->instructions);
        }
        $lines[] = 'Return a JSON object with exactly these keys:';
        $lines[] = '- "title": final title (string)';
        $lines[] = '- "slug": URL slug in lowercase latin letters, digits and hyphens, at most 80 characters';
        $lines[] = '- "excerpt": 1-2 sentence summary, at most 300 characters';
        $lines[] = '- "category": '.($categories ? 'one value from the list above' : 'empty string');
        $lines[] = '- "meta_title": SEO title, at most 60 characters';
        $lines[] = '- "meta_description": SEO description, at most 155 characters';
        $lines[] = '- "keywords": array of 5 to 8 short keywords';
        $lines[] = '- "body": the article as HTML. Use only <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>. '
            .'No <h1>, no images, no links, no inline styles. Start with an introduction paragraph.';
        if ($imageCount > 0) {
            $lines[] = '- "image_prompts": array of exactly '.$imageCount.' prompts in English for an image generator. '
                .'The first is the cover. Describe a realistic photo or a clean illustration that fits the article. '
                .'No text, letters, logos or watermarks in the image.';
            $lines[] = '- "image_alts": array of exactly '.$imageCount.' short alt texts in '.$language.'.';
        }
        $user = implode("\n", $lines);

        $last = null;
        for ($try = 1; $try <= 3; $try++) {
            try {
                $data = $this->ai->text($agent)->json($system, $user, ['temperature' => 1.0]);
                $body = $this->cleanBody((string) ($data['body'] ?? ''));
                if (trim(strip_tags($body)) === '' || trim((string) ($data['title'] ?? '')) === '') {
                    throw new AiException('The AI reply has no title or body.');
                }

                return $this->normalise($data, $body, $categories);
            } catch (AiException $e) {
                $last = $e;
            }
        }

        throw $last;
    }

    private function normalise(array $data, string $body, array $categories): array
    {
        $title = Str::limit(trim(strip_tags((string) $data['title'])), 480, '');
        $slug = Str::slug((string) ($data['slug'] ?? '')) ?: Str::slug($title) ?: Str::lower(Str::random(10));

        return [
            'title' => $title,
            'slug' => Str::limit($slug, 80, ''),
            'excerpt' => Str::limit(trim(strip_tags((string) ($data['excerpt'] ?? ''))), 600, ''),
            'category' => $this->matchCategory((string) ($data['category'] ?? ''), $categories),
            'meta_title' => Str::limit(trim(strip_tags((string) ($data['meta_title'] ?? $title))), 250, ''),
            'meta_description' => Str::limit(trim(strip_tags((string) ($data['meta_description'] ?? ''))), 490, ''),
            'keywords' => array_values(array_slice(array_filter(array_map(fn ($k) => trim(strip_tags((string) $k)),
                (array) ($data['keywords'] ?? []))), 0, 12)),
            'body' => $body,
            'word_count' => self::countWords($body),
            '_prompts' => array_values(array_map('strval', (array) ($data['image_prompts'] ?? []))),
            '_alts' => array_values(array_map('strval', (array) ($data['image_alts'] ?? []))),
        ];
    }

    /** Exact match first (ignoring case), then the most similar one. */
    private function matchCategory(string $wanted, array $categories): ?string
    {
        if ($categories === []) {
            return null;
        }
        $wanted = trim($wanted);
        foreach ($categories as $c) {
            if (mb_strtolower($c) === mb_strtolower($wanted)) {
                return $c;
            }
        }
        $best = $categories[0];
        $bestScore = -1;
        foreach ($categories as $c) {
            similar_text(mb_strtolower($c), mb_strtolower($wanted), $pct);
            if ($pct > $bestScore) {
                [$best, $bestScore] = [$c, $pct];
            }
        }

        return $best;
    }

    private function cleanBody(string $html): string
    {
        $html = strip_tags($html, '<h2><h3><h4><p><ul><ol><li><strong><em><b><i><blockquote><br>');
        // Drop every attribute: the allowed tags need none.
        $html = preg_replace('~<(\w+)\s[^>]*>~u', '<$1>', $html);

        return trim(preg_replace('~<h1>(.*?)</h1>~is', '<h2>$1</h2>', $html));
    }

    public static function countWords(string $html): int
    {
        return preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\p{M}\'’-]*/u', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }

    private function drawImage(Article $article, AiAgent $agent, ImageSize $size, int $position, string $prompt, string $alt): bool
    {
        $image = $article->images()->create([
            'position' => $position,
            'image_size_id' => $size->id,
            'size_name' => $size->name,
            'width' => $size->width,
            'height' => $size->height,
            'alt' => Str::limit($alt, 480, ''),
            'prompt' => $prompt,
        ]);

        $error = null;
        for ($try = 1; $try <= 2; $try++) {
            try {
                $raw = $this->ai->image($agent)->image($prompt, $size->width, $size->height);
                $jpeg = ImageProcessor::toJpeg($raw, $size->width, $size->height);
                $path = 'media/'.$article->created_at->format('Y/m').'/'.$image->uuid.'.jpg';
                Storage::disk('local')->put($path, $jpeg);
                $image->update(['path' => $path, 'bytes' => strlen($jpeg), 'mime' => 'image/jpeg', 'error' => null]);

                return true;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
        $image->update(['error' => mb_substr((string) $error, 0, 1000)]);

        return false;
    }

    private function textCost(AiAgent $agent, int $words): float
    {
        return match ($agent->price_unit) {
            'per_1k_words' => (float) $agent->price * max($words, 0) / 1000,
            default => (float) $agent->price,
        };
    }

    private function imageCost(AiAgent $agent, int $images): float
    {
        return $images <= 0 ? 0.0 : match ($agent->price_unit) {
            'per_image' => (float) $agent->price * $images,
            default => (float) $agent->price,
        };
    }
}
