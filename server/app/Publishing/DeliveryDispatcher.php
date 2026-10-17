<?php

namespace App\Publishing;

use App\Jobs\SendDelivery;
use App\Models\Article;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Creates deliveries for a ready article, sends them, and plans retries. */
class DeliveryDispatcher
{
    public function __construct(private PublisherRegistry $registry)
    {
    }

    public function queueFor(Article $article): void
    {
        if ($article->status !== Article::READY || ! $article->service) {
            return;
        }
        $service = $article->service;
        $max = 1 + min((int) $service->retry_limit, \App\Models\Service::MAX_RETRIES);

        if ($service->webhook_url) {
            $this->create($article, null, 'webhook', $service->webhook_url, $max);
        }
        foreach ($service->destinations()->where('is_active', true)->get() as $dest) {
            if ($this->registry->has($dest->driver)) {
                $this->create($article, $dest->id, $dest->driver,
                    $dest->name.' · '.$dest->publisher()->target($dest->config ?? []), $max);
            }
        }
    }

    private function create(Article $article, ?int $destinationId, string $driver, string $target, int $max): void
    {
        $delivery = $article->deliveries()->create([
            'destination_id' => $destinationId,
            'driver' => $driver,
            'target' => mb_substr($target, 0, 1000),
            'status' => Delivery::PENDING,
            'max_attempts' => $max,
        ]);
        SendDelivery::dispatch($delivery->id);
    }

    /** Send again now, with a fresh set of attempts. */
    public function resend(Delivery $delivery): void
    {
        $delivery->update(['status' => Delivery::PENDING, 'attempts' => 0, 'next_attempt_at' => null, 'error' => null]);
        SendDelivery::dispatch($delivery->id);
    }

    public function send(Delivery $delivery): void
    {
        $claimed = DB::table('deliveries')->where('id', $delivery->id)->where('status', Delivery::PENDING)
            ->update(['status' => Delivery::SENDING, 'attempts' => DB::raw('attempts + 1'), 'next_attempt_at' => null,
                'updated_at' => now()]);
        if (! $claimed) {
            return; // already sent, or sent by another worker
        }
        $delivery->refresh();
        $article = $delivery->article()->with(['service', 'images'])->first();
        if (! $article || ! $article->service) {
            $delivery->update(['status' => Delivery::FAILED, 'error' => 'The article or service was deleted.']);

            return;
        }

        try {
            [$publisher, $config] = $this->resolve($delivery, $article);
            $result = $publisher->publish($article, $config, $delivery);
            $delivery->update([
                'status' => Delivery::SUCCESS,
                'response_code' => $result->code,
                'response' => $result->response ? mb_substr($result->response, 0, 2000) : null,
                'remote_url' => $result->remoteUrl ? mb_substr($result->remoteUrl, 0, 1000) : null,
                'error' => null,
                'delivered_at' => now(),
            ]);
        } catch (Throwable $e) {
            $retry = $delivery->attempts < $delivery->max_attempts;
            $delivery->update([
                'status' => $retry ? Delivery::PENDING : Delivery::FAILED,
                'next_attempt_at' => $retry ? now()->addMinutes(max(1, (int) $article->service->retry_delay)) : null,
                'response_code' => $e instanceof PublishException ? $e->status : null,
                'response' => $e instanceof PublishException ? $e->responseBody : null,
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);
        }
    }

    /** @return array{0: Publisher, 1: array} */
    private function resolve(Delivery $delivery, Article $article): array
    {
        if ($delivery->destination_id === null) {
            $service = $article->service;

            return [$this->registry->get('webhook'), [
                'url' => $service->webhook_url,
                'secret' => $service->webhook_secret,
                'embed_images' => $service->webhook_embed_images,
            ]];
        }
        $dest = $delivery->destination ?? throw new PublishException('The destination was deleted.');
        if (! $dest->is_active) {
            throw new PublishException('The destination is disabled.');
        }

        return [$dest->publisher(), $dest->config ?? []];
    }

    /** Dispatch the retries that are due. Called every minute by the scheduler. */
    public function dispatchDue(): int
    {
        $ids = Delivery::query()->where('status', Delivery::PENDING)->whereNotNull('next_attempt_at')
            ->where('next_attempt_at', '<=', now())->limit(200)->pluck('id');
        foreach ($ids as $id) {
            Delivery::query()->whereKey($id)->update(['next_attempt_at' => null]);
            SendDelivery::dispatch($id);
        }

        return $ids->count();
    }
}
