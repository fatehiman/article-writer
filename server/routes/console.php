<?php

use App\Generation\ArticleFactory;
use App\Models\AiProvider;
use App\Models\Article;
use App\Models\Service;
use App\Models\User;
use App\Publishing\DeliveryDispatcher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Start an article for every service whose next run time has come.
Artisan::command('articles:dispatch-due', function (ArticleFactory $factory) {
    // Articles stuck in "generating" (worker killed) are marked failed.
    Article::query()->where('status', Article::GENERATING)->where('updated_at', '<', now()->subMinutes(45))
        ->update(['status' => Article::FAILED, 'error' => 'Generation took too long and was stopped.']);

    $count = 0;
    Service::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '<=', now())
        ->orderBy('next_run_at')->limit(100)->get()
        ->each(function (Service $service) use ($factory, &$count) {
            // Move the clock first, so a crash never starts the same run twice.
            $service->forceFill(['last_run_at' => now(), 'next_run_at' => $service->computeNextRun(now())])->save();
            $factory->start($service, 'schedule');
            $count++;
        });
    $this->info("Started {$count} article(s).");
})->purpose('Start articles for services that are due');

Artisan::command('deliveries:retry', function (DeliveryDispatcher $dispatcher) {
    $this->info('Queued '.$dispatcher->dispatchDue().' retry(s).');
})->purpose('Queue webhook/destination retries that are due');

// php artisan ai:set-key 1 < keyfile   (the key is read from STDIN, never from the command line)
Artisan::command('ai:set-key {provider : provider id or exact name}', function (string $provider) {
    $row = AiProvider::query()->whereKey(is_numeric($provider) ? (int) $provider : 0)->orWhere('name', $provider)->first();
    if (! $row) {
        $this->error('Provider not found.');

        return 1;
    }
    $key = trim((string) stream_get_contents(STDIN));
    if ($key === '') {
        $this->error('No key on STDIN.');

        return 1;
    }
    $row->update(['api_key' => $key]);
    $this->info("Key saved for {$row->name} (".strlen($key).' chars).');
})->purpose('Set the API key of an AI provider from STDIN');

// printf '%s' 'the-password' | php artisan users:admin mainadmin
Artisan::command('users:admin {username} {--name=}', function (string $username) {
    $password = rtrim((string) stream_get_contents(STDIN), "\r\n");
    if (strlen($password) < 8) {
        $this->error('Give a password of 8+ characters on STDIN.');

        return 1;
    }
    $user = User::query()->firstOrNew(['username' => strtolower($username)]);
    $user->fill(['name' => $this->option('name') ?: ($user->name ?: $username), 'password' => $password,
        'role' => User::ROLE_ADMIN, 'is_active' => true]);
    $user->save();
    $this->info("Admin {$user->username} saved.");
})->purpose('Create or reset an admin user (password from STDIN)');

Schedule::command('articles:dispatch-due')->everyMinute()->withoutOverlapping(10);
Schedule::command('deliveries:retry')->everyMinute()->withoutOverlapping(10);
// No long-running worker on the shared host: the scheduler starts one that exits when the queue is empty.
Schedule::command('queue:work --stop-when-empty --tries=1 --timeout=1500 --max-time=3300')
    ->everyMinute()->withoutOverlapping(60)->runInBackground();
Schedule::command('queue:prune-failed --hours=720')->daily();
