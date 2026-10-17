<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_and_login_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('Under construction');
        $this->get('/mainadmin')->assertOk()->assertSee('Sign in');
        $this->get('/locale/fa')->assertRedirect();
        $this->get('/mainadmin')->assertOk()->assertSee('dir="rtl"', false)->assertSee('theme-rtl.css', false);
    }

    public function test_login_with_username(): void
    {
        User::factory()->create(['username' => 'mainadmin', 'password' => 'Secret-123', 'role' => 'admin']);
        $this->post('/mainadmin', ['username' => 'MainAdmin', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->post('/mainadmin', ['username' => 'MainAdmin', 'password' => 'Secret-123'])->assertRedirect(route('dashboard'));
    }

    public function test_customer_cannot_open_admin_pages(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_every_panel_page_renders_in_both_themes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = $this->makeService($admin);

        $pages = [
            route('dashboard'), route('services.index'), route('services.create'), route('services.show', $service),
            route('services.edit', $service), route('title-lists.index'), route('title-lists.create'),
            route('title-lists.edit', $service->titleList), route('articles.index'), route('destinations.index'),
            route('destinations.create', ['driver' => 'wordpress']), route('destinations.create', ['driver' => 'telegram']),
            route('destinations.create', ['driver' => 'instagram']), route('destinations.create', ['driver' => 'linkedin']),
            route('wallet'), route('docs'), route('docs', ['service' => $service->id]), route('profile'),
            route('admin.customers.index'), route('admin.customers.create'), route('admin.customers.edit', $admin),
            route('admin.providers.index'), route('admin.providers.create'), route('admin.agents.index'),
            route('admin.agents.create'), route('admin.image-sizes.index'), route('admin.image-sizes.create'),
            route('admin.tones.index'), route('admin.tones.create'), route('admin.settings'),
        ];

        foreach (['en', 'fa'] as $locale) {
            $admin->update(['locale' => $locale]);
            foreach ($pages as $url) {
                $res = $this->actingAs($admin->fresh())->get($url);
                $this->assertSame(200, $res->status(), "{$locale} {$url}");
                $res->assertDontSee('ui.', false);
            }
        }
    }

    public function test_customers_only_see_their_own_services(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = $this->makeService($owner);
        $this->actingAs($other)->get(route('services.show', $service))->assertNotFound();
    }

    public function test_create_service_with_form(): void
    {
        $user = User::factory()->create();
        $list = $user->titleLists()->create(['name' => 'Psychology']);
        $list->syncTitles("Title one\nTitle two");

        $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Blog', 'is_active' => '1', 'title_list_id' => $list->id, 'categories' => "Mind\nBody",
            'word_count' => 600, 'language' => 'en', 'text_agent_id' => 1, 'image_count' => 2, 'images' => [1, 3],
            'image_agent_id' => 2, 'timezone' => 'Asia/Tehran', 'schedule_times' => ['09:00', '21:30'],
            'schedule_type' => 'weekly', 'schedule_days' => [1, 3], 'retry_limit' => 3, 'retry_delay' => 2,
        ])->assertRedirect();

        $s = Service::query()->firstOrFail();
        $this->assertSame(['Mind', 'Body'], $s->categories);
        $this->assertSame([1, 3], $s->images);
        $this->assertNotNull($s->next_run_at);
        $this->assertStringStartsWith('aw_', $s->api_token);
        $this->assertNotNull(Service::findByToken($s->api_token));
    }

    public function test_quick_title_list_from_modal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('title-lists.quick'), ['name' => 'Soft', 'titles' => "A\nB\n\nA"])
            ->assertOk()->assertJson(['name' => 'Soft', 'count' => 2]);
    }

    private function makeService(User $user): Service
    {
        $list = $user->titleLists()->create(['name' => 'List '.$user->id]);
        $list->syncTitles("First title\nSecond title");
        $service = new Service([
            'name' => 'Test service', 'is_active' => true, 'title_list_id' => $list->id, 'categories' => ['Tech', 'Life'],
            'word_count' => 500, 'language' => 'en', 'text_agent_id' => 1, 'image_agent_id' => 2, 'images' => [3],
            'timezone' => 'UTC', 'schedule_times' => ['09:00'], 'schedule_type' => 'daily', 'retry_limit' => 2, 'retry_delay' => 1,
        ]);
        $service->user_id = $user->id;
        $service->rotateToken();
        $service->rotateWebhookSecret();
        $service->refreshNextRun();
        $service->save();

        return $service;
    }
}
