# Article Writer - notes for Claude

- Laravel app lives in `server/`. Read `README.md` for the code map.
- Public GitHub repo (`fatehiman/article-writer`): **never commit secrets** (keys, passwords, `.env`).
  The admin password and AI keys are set on the server through STDIN (`users:admin`, `ai:set-key`).
- Deploy only with `bash deploy/deploy.sh` (ger1, user `articles`, app `/mnt/ger_hd1/www/articles/app`,
  docroot `app/public`). It deploys the last commit. `reference-docs/` and `*.md` are never deployed.
- UI rules: icon-only buttons with tooltip (`icon_btn()` helper) and soft tones; dialog/form buttons keep text.
  Placeholders are light gray (`--aw-placeholder`) so they never look like typed text. Number fields are small:
  `.aw-num` (one box) or `.aw-num-group` (box + unit); put short number fields in one sentence with `.aw-inline-nums`
  instead of side-by-side columns with long labels.
- After a CSS/JS change, raise the `?v=` number in `partials/head.blade.php` / the layouts.
- Every UI text goes through `__('ui.key')`; add the key to **both** `lang/en/ui.php` and `lang/fa/ui.php`.
- RTL languages have their own theme (`resources/views/themes/rtl`, `public/css/theme-rtl.css`). Keep both themes
  working; shared component styles are in `public/css/base.css`.
- The webhook/API JSON (`app/Publishing/Payload.php`) is a public contract. Add fields only; never rename or remove.
- New AI vendors / publish platforms: add a driver class and list it in `config/ai.php` / `config/publishers.php`.
- Run `php artisan test` before committing.
