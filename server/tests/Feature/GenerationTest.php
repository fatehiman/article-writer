<?php

namespace Tests\Feature;

use App\Generation\ArticleFactory;
use App\Models\AiAgent;
use App\Models\AiProvider;
use App\Models\Article;
use App\Models\Delivery;
use App\Models\Service;
use App\Models\User;
use App\Publishing\Payload;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        config(['publishers.block_private_hosts' => false]);
        AiProvider::query()->get()->each->update(['api_key' => 'test-key']);

        $this->user = User::factory()->create();
        $list = $this->user->titleLists()->create(['name' => 'Psy']);
        $list->syncTitles("Sleep and mood\nWhy we wait");

        $service = new Service([
            'name' => 'Blog', 'is_active' => true, 'title_list_id' => $list->id, 'categories' => ['Psychology', 'Health'],
            'word_count' => 300, 'language' => 'en', 'text_agent_id' => 1, 'image_agent_id' => 2, 'images' => [3, 2],
            'timezone' => 'UTC', 'schedule_times' => ['09:00'], 'schedule_type' => 'daily',
            'webhook_url' => 'https://hooks.example.test/in', 'retry_limit' => 2, 'retry_delay' => 1,
        ]);
        $service->user_id = $this->user->id;
        $service->rotateToken();
        $service->rotateWebhookSecret();
        $service->save();
        $this->service = $service;
    }

    private function fakeAi(int $webhookStatus = 200): void
    {
        $img = imagecreatetruecolor(1664, 928);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();

        Http::fake([
            'api.deepseek.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
                'title' => 'How Sleep Shapes Mood', 'slug' => 'how-sleep-shapes-mood', 'excerpt' => 'Short.',
                'category' => 'psychology', 'meta_title' => 'Sleep', 'meta_description' => 'About sleep',
                'keywords' => ['sleep', 'mood'], 'body' => '<p style="x">Intro text here.</p><h2>Part</h2><p>More words.</p><p>End.</p>',
                'image_prompts' => ['a bed', 'a clock'], 'image_alts' => ['Bed', 'Clock'],
            ])]]]]),
            'api.siliconflow.com/*' => Http::response(['images' => [['url' => 'https://cdn.example.test/x.png']]]),
            'cdn.example.test/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
            'hooks.example.test/*' => Http::response('ok', $webhookStatus),
        ]);
    }

    public function test_full_run_writes_article_draws_images_and_calls_webhook(): void
    {
        $this->fakeAi();
        $article = app(ArticleFactory::class)->start($this->service, 'manual')->fresh();

        $this->assertSame(Article::READY, $article->status, (string) $article->error);
        $this->assertSame('Psychology', $article->category);       // matched to the exact category
        $this->assertSame('Sleep and mood', $article->source_title);
        $this->assertStringNotContainsString('style=', $article->body);
        $this->assertCount(2, $article->readyImages());
        [$w, $h] = getimagesizefromstring($article->readyImages()[0]->contents());
        $this->assertSame([1024, 1024], [$w, $h]);                  // cropped to "Large"
        $this->assertStringContainsString('<figure>', $article->bodyWithImages());

        $delivery = $article->deliveries()->first();
        $this->assertSame(Delivery::SUCCESS, $delivery->status);
        Http::assertSent(function (Request $req) {
            if (! str_contains($req->url(), 'hooks.example.test')) {
                return false;
            }
            $body = $req->body();
            preg_match('/t=(\d+),v1=([0-9a-f]+)/', $req->header('X-AW-Signature')[0], $m);
            $data = json_decode($body, true);

            return $data['event'] === 'article.created' && count($data['article']['images']) === 2
                && hash_equals(Payload::signature($body, $this->service->webhook_secret, (int) $m[1]), $req->header('X-AW-Signature')[0]);
        });

        $this->assertSame(1, $this->service->titleList->titles()->where('used_count', 1)->count());
    }

    public function test_failed_webhook_is_retried_later(): void
    {
        $this->fakeAi(500);
        $article = app(ArticleFactory::class)->start($this->service, 'manual');
        $delivery = $article->deliveries()->first();

        $this->assertSame(Delivery::PENDING, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame(3, $delivery->max_attempts);
        $this->assertNotNull($delivery->next_attempt_at);

        $this->travel(2)->minutes();
        $this->artisan('deliveries:retry')->assertSuccessful();
        $delivery->refresh();
        $this->assertSame(2, $delivery->attempts);

        $this->travel(2)->minutes();
        $this->artisan('deliveries:retry');
        $this->assertSame(Delivery::FAILED, $delivery->fresh()->status);
    }

    public function test_titles_run_out_without_reuse(): void
    {
        $this->fakeAi();
        $factory = app(ArticleFactory::class);
        $factory->start($this->service);
        $factory->start($this->service);
        $third = $factory->start($this->service);
        $this->assertSame(Article::FAILED, $third->status);
    }

    public function test_wallet_is_charged_and_blocks_when_empty(): void
    {
        $this->fakeAi();
        AiAgent::query()->whereKey(1)->update(['price' => 1, 'price_unit' => 'per_request']);
        $blocked = app(ArticleFactory::class)->start($this->service)->fresh();
        $this->assertSame(Article::FAILED, $blocked->status);

        \App\Support\Wallet::change($this->user, 5, 'deposit');
        $ok = app(ArticleFactory::class)->start($this->service)->fresh();
        $this->assertSame(Article::READY, $ok->status);
        $this->assertEquals(4.0, (float) $this->user->fresh()->wallet_balance);
    }

    public function test_due_services_are_started_by_the_scheduler_command(): void
    {
        $this->fakeAi();
        $this->service->forceFill(['next_run_at' => now()->subMinute()])->save();
        $this->artisan('articles:dispatch-due')->assertSuccessful();
        $this->assertSame(1, $this->service->articles()->count());
        $this->assertTrue($this->service->fresh()->next_run_at->isFuture());
    }

    public function test_api_with_token(): void
    {
        $this->fakeAi();
        $article = app(ArticleFactory::class)->start($this->service);

        $this->getJson('/api/v1/articles')->assertUnauthorized();
        $this->getJson('/api/v1/articles', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();

        $auth = ['Authorization' => 'Bearer '.$this->service->api_token];
        $this->getJson('/api/v1/articles', $auth)->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $article->uuid);
        $this->getJson('/api/v1/articles/'.$article->uuid.'?embed_images=1', ['X-Service-Token' => $this->service->api_token])
            ->assertOk()->assertJsonStructure(['data' => ['images' => [['base64', 'url']]]]);
        $this->getJson('/api/v1/service', $auth)->assertOk()->assertJsonPath('data.name', 'Blog');
        $this->getJson('/api/openapi.json')->assertOk()->assertJsonPath('openapi', '3.0.3');

        $img = $article->images()->first();
        $this->get('/media/'.$img->uuid.'.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }
}
