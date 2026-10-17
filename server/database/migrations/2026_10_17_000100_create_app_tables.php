<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // An AI API account (DeepSeek, SiliconFlow, OpenAI...). Only admins manage these.
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 40);          // see config/ai.php
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();   // encrypted
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // A model on a provider, offered to customers with a price.
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 16);            // text | image | video
            $table->string('model');
            $table->text('description')->nullable();
            $table->json('options')->nullable();   // temperature, native sizes, ...
            $table->decimal('price', 14, 4)->default(0);
            $table->string('price_unit', 24);      // per_1k_words | per_image | per_request
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('image_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group', 40)->default('general');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('labels')->nullable();    // {"fa": "..."} translated names
            $table->text('instruction')->nullable(); // extra hint for the AI
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('title_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_list_id')->constrained()->cascadeOnDelete();
            $table->string('title', 500);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['title_list_id', 'used_count']);
        });

        // Saved publish targets: WordPress site, Telegram channel, Instagram page...
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('driver', 40);          // see config/publishers.php
            $table->text('config')->nullable();    // encrypted JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('title_list_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('reuse_titles')->default(false);
            $table->json('categories')->nullable();
            $table->unsignedInteger('word_count')->default(800);
            $table->foreignId('tone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('language', 12)->default('en');
            $table->text('instructions')->nullable();
            $table->foreignId('text_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('image_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->json('images')->nullable();    // [image_size_id, ...] up to 10
            $table->string('timezone', 64)->default('UTC');
            $table->json('schedule_times')->nullable(); // ["09:00", "18:30"]
            $table->string('schedule_type', 16)->default('daily'); // daily | weekly | monthly
            $table->json('schedule_days')->nullable();  // weekly: 0-6 (Sun-Sat), monthly: 1-31
            $table->string('webhook_url', 1000)->nullable();
            $table->text('webhook_secret')->nullable(); // encrypted
            $table->boolean('webhook_embed_images')->default(false);
            $table->unsignedTinyInteger('retry_limit')->default(5);
            $table->unsignedSmallInteger('retry_delay')->default(3); // minutes
            $table->string('api_token_hash', 64)->nullable()->unique();
            $table->text('api_token')->nullable(); // encrypted, so the owner can see it again
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('destination_service', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'destination_id']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('title_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending'); // pending | generating | ready | failed
            $table->string('source_title', 500)->nullable();
            $table->string('title', 500)->nullable();
            $table->string('slug', 200)->nullable();
            $table->string('category')->nullable();
            $table->string('language', 12);
            $table->string('tone')->nullable();
            $table->unsignedInteger('requested_words')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();  // HTML
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->json('keywords')->nullable();
            $table->foreignId('text_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('image_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->decimal('cost', 14, 4)->default(0);
            $table->text('error')->nullable();
            $table->string('trigger', 16)->default('schedule'); // schedule | manual
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->index(['service_id', 'status', 'id']);
        });

        Schema::create('article_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->foreignId('image_size_id')->nullable()->constrained()->nullOnDelete();
            $table->string('size_name')->nullable();
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->string('path')->nullable();
            $table->string('mime', 32)->default('image/jpeg');
            $table->unsignedInteger('bytes')->default(0);
            $table->string('alt', 500)->nullable();
            $table->text('prompt')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // One row per (article, target). The main service webhook has destination_id = null.
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver', 40);
            $table->string('target', 1000)->nullable(); // URL or name, for display
            $table->string('status', 16)->default('pending'); // pending | sending | success | failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response')->nullable();
            $table->string('remote_url', 1000)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 4);      // + deposit, - charge
            $table->decimal('balance_after', 14, 4);
            $table->string('type', 16);            // deposit | charge | refund | adjust
            $table->string('description')->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['wallet_transactions', 'deliveries', 'article_images', 'articles', 'destination_service',
            'services', 'destinations', 'titles', 'title_lists', 'tones', 'image_sizes', 'ai_agents',
            'ai_providers', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
