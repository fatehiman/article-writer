<?php

namespace App\Publishing;

use App\Models\Article;
use App\Models\Delivery;

/**
 * A place where articles can be published (webhook, WordPress, Telegram...).
 * Labels of the fields come from lang/<locale>/publishers.php: "<key>.<field>".
 */
interface Publisher
{
    public function key(): string;

    /** Bootstrap Icons class name, e.g. "bi-wordpress". */
    public function icon(): string;

    /** Tone name used for the icon box: violet, mint, amber, sky, rose, peach. */
    public function tone(): string;

    /**
     * Form fields: name => [type, required, default, options, secret].
     * type: text | url | password | textarea | select | checkbox
     */
    public function fields(): array;

    /** Short text that tells which target this is ("example.com", "@channel"). */
    public function target(array $config): string;

    /** Throws PublishException on failure. */
    public function publish(Article $article, array $config, Delivery $delivery): PublishResult;
}
