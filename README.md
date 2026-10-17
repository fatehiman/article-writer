# Article Writer

A light control panel where customers schedule AI-written articles (text + images) and publish them
automatically to their websites and social media.

Live: **https://articles.peppasoft.com** (home page is "under construction" on purpose; the panel is at `/mainadmin`).

## What it does

- **Two roles:** `admin` and `customer`. There is no sign-up; admins create users.
- **Title lists:** a customer saves named groups of titles (e.g. "Psychology" with 100 titles). Each article is
  written from the next unused title of the list.
- **Services:** a service says *what* to write and *when*: title list, categories (the AI picks the best one),
  length in words, tone, language, extra instructions, text AI, 0–10 images (each with an admin-defined size),
  run times (HH:MM) + repeat (every day / days of the week / days of the month) in a time zone.
- **Delivery:** each new article is sent to
  - the service **webhook** (one JSON POST with the article and all images, signed with HMAC-SHA256,
    retried up to 10 times with a delay the customer chooses), and
  - any number of **destinations**: WordPress, Telegram, Instagram, Facebook page, LinkedIn, or more webhooks.
- **Pull API:** each service has a token (can be rotated). `GET /api/v1/articles`, `/api/v1/articles/{id}`,
  `/api/v1/service`. OpenAPI file: `/api/openapi.json`. Docs page in the panel: **API & docs**.
- **AI is provider-agnostic.** Admins add *providers* (API accounts) and *agents* (models with a price).
  Customers only choose agents. Built-in drivers: OpenAI-compatible chat (DeepSeek, OpenAI, OpenRouter...),
  Anthropic Claude, SiliconFlow images, OpenAI images. Kind `video` is reserved for later.
- **Wallet (pay as you go):** agents have a price (per 1000 words, per image or per request). The real cost is
  taken from the customer's wallet after each article. Admins add money. With the setting "block when the
  wallet is too low", articles are not written without enough money.
- **Multilingual UI:** English (default) and Persian. RTL languages use their **own theme**
  (`resources/views/themes/rtl`, `public/css/theme-rtl.css`), not only a mirrored layout.

## Stack

Laravel 13, PHP 8.4, MySQL, Blade, Bootstrap 5 + Bootstrap Icons from CDN. No npm build.
Queue and cache use the database. There is no long-running worker: the scheduler (cron, every minute)
starts `queue:work --stop-when-empty`.

## Code map (`server/`)

| Path | What |
|---|---|
| `app/Ai/` | AI drivers (`Drivers/*`), `AiManager`, `ImageProcessor` (crop to exact size, JPEG) |
| `config/ai.php` | list of AI drivers, kinds, price units |
| `app/Generation/` | `ArticleFactory` (picks a title, makes the article row), `ArticleGenerator` (prompt, images, wallet) |
| `app/Publishing/` | `Publisher` interface, `Drivers/*` (webhook, wordpress, telegram, instagram, facebook, linkedin), `DeliveryDispatcher` (send + retries), `Payload` (the JSON format) |
| `config/publishers.php` | list of publish drivers |
| `app/Support/Schedule.php` | next run time of a service |
| `routes/console.php` | `articles:dispatch-due`, `deliveries:retry`, `ai:set-key`, `users:admin`, and the schedule |
| `lang/{en,fa}/` | `ui.php` (panel texts), `publishers.php` (destination forms) |
| `config/languages.php` | UI languages (+ which theme), article languages |
| `resources/views/themes/{ltr,rtl}/` | the two themes (layout + login) |

### Add a new platform (e.g. X, Pinterest)

1. Write `app/Publishing/Drivers/XPublisher.php` that extends `BasePublisher` (fields, target, publish).
2. Add it to `config/publishers.php`.
3. Add its texts to `lang/*/publishers.php`.

### Add a new AI vendor

1. Write a class in `app/Ai/Drivers` that implements `TextDriver` or `ImageDriver`.
2. Add it to `config/ai.php`. Then add a provider and agents in the admin panel.

### Add a UI language

Copy `lang/en` to `lang/<code>` and translate, then add the language to `config/languages.php`
(`'theme' => 'rtl'` for right-to-left languages).

## Local development

```bash
cd server
composer install
cp .env.example .env && php artisan key:generate     # DB_CONNECTION=sqlite is fine locally
touch database/database.sqlite
php artisan migrate --seed
printf '%s' 'a-strong-password' | php artisan users:admin mainadmin --name="Main Admin"
php artisan serve                                    # http://127.0.0.1:8000/mainadmin
php artisan test
```

To run the scheduler locally: `php artisan schedule:work`.

## Deploy (ger1)

```bash
bash deploy/deploy.sh      # from the repo root, after a commit
```

The script uploads `git archive HEAD:server`, runs composer, migrations, the (idempotent) seeder and
`optimize`, writes `/etc/cron.d/articles`, restarts the queue worker and flushes opcache.
`reference-docs/` and `*.md` files are never deployed.

### First-time server setup (done on 2026-10-17)

```bash
virtualmin enable-feature --domain articles.peppasoft.com --mysql      # database "articles", user "articles"
virtualmin modify-web --domain articles.peppasoft.com --document-dir app/public
# write /mnt/ger_hd1/www/articles/app/.env (APP_ENV=production, APP_URL=https://articles.peppasoft.com,
#   DB_CONNECTION=mysql, DB_DATABASE=articles, DB_USERNAME=articles, DB_PASSWORD=...) - chmod 600
bash deploy/deploy.sh
# admin user and AI keys - read from STDIN, never on the command line:
printf '%s' "$PASSWORD" | php8.4 artisan users:admin mainadmin --name="Main Admin"
php8.4 artisan ai:set-key DeepSeek < keyfile
php8.4 artisan ai:set-key SiliconFlow < keyfile
```

Secrets (`.env`, DB password, AI keys) are never committed.
