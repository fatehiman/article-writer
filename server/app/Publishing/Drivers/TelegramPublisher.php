<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\PublishException;
use App\Publishing\PublishResult;

/**
 * Telegram Bot API. The bot must be an admin of the channel/group.
 * mode "summary": cover photo + title + excerpt. mode "full": then the whole text in parts.
 */
class TelegramPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'telegram';
    }

    public function icon(): string
    {
        return 'bi-telegram';
    }

    public function tone(): string
    {
        return 'sky';
    }

    public function fields(): array
    {
        return [
            'bot_token' => ['type' => 'password', 'required' => true, 'secret' => true],
            'chat_id' => ['type' => 'text', 'required' => true],
            'mode' => ['type' => 'select', 'options' => ['summary', 'full'], 'default' => 'full'],
        ];
    }

    public function target(array $config): string
    {
        return (string) ($config['chat_id'] ?? '');
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'bot_token', 'chat_id');
        $api = 'https://api.telegram.org/bot'.trim($config['bot_token']);
        $chat = trim($config['chat_id']);
        $head = '<b>'.e($article->title).'</b>';
        $tags = e($article->hashtags(6));
        $cover = $article->readyImages()->first();
        $full = ($config['mode'] ?? 'full') === 'full';

        $caption = $head."\n\n".e($this->cut((string) $article->excerpt, 800)).($full ? '' : "\n\n".$tags);
        if ($cover) {
            $res = $this->http()->attach('photo', $cover->contents(), 'cover.jpg')
                ->post($api.'/sendPhoto', ['chat_id' => $chat, 'caption' => $this->cut($caption, 1000), 'parse_mode' => 'HTML']);
        } else {
            $res = $this->http()->asForm()->post($api.'/sendMessage', ['chat_id' => $chat, 'text' => $caption, 'parse_mode' => 'HTML']);
        }
        $this->check($res);
        $first = $res->json('result.message_id');

        if ($full) {
            foreach ($this->chunks($this->telegramHtml((string) $article->body)."\n\n".$tags, 3900) as $part) {
                $this->check($this->http()->asForm()->post($api.'/sendMessage',
                    ['chat_id' => $chat, 'text' => $part, 'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true']));
            }
        }

        $link = str_starts_with($chat, '@') && $first ? 'https://t.me/'.ltrim($chat, '@').'/'.$first : null;

        return new PublishResult(200, 'message id '.$first, $link);
    }

    private function check($res): void
    {
        if (! $res->successful() || ! $res->json('ok')) {
            throw PublishException::fromResponse('Telegram', $res);
        }
    }

    /** Telegram allows only a few tags: b, i, u, s, a, code, pre, blockquote. */
    private function telegramHtml(string $html): string
    {
        $html = preg_replace('~<h[2-6][^>]*>(.*?)</h[2-6]>~is', "\n<b>$1</b>\n", $html);
        $html = preg_replace(['~<li[^>]*>~i', '~</li>~i', '~</p>~i', '~<br\s*/?>~i'], ['• ', "\n", "\n\n", "\n"], $html);
        $html = str_ireplace(['<strong>', '</strong>', '<em>', '</em>'], ['<b>', '</b>', '<i>', '</i>'], $html);
        $html = strip_tags($html, '<b><i><blockquote>');

        return trim(preg_replace("/\n{3,}/", "\n\n", $html));
    }

    /** Split on paragraph ends so no tag is cut in half. */
    private function chunks(string $text, int $max): array
    {
        $out = [];
        $buf = '';
        foreach (preg_split("/\n\n/", $text) as $para) {
            if ($buf !== '' && mb_strlen($buf) + mb_strlen($para) + 2 > $max) {
                $out[] = $buf;
                $buf = '';
            }
            $buf .= ($buf === '' ? '' : "\n\n").(mb_strlen($para) > $max ? strip_tags($this->cut($para, $max)) : $para);
        }
        if (trim($buf) !== '') {
            $out[] = $buf;
        }

        return $out;
    }
}
