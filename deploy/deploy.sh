#!/usr/bin/env bash
# Deploy Article Writer (Laravel, folder server/) to ger1, from the last git commit.
# Run from the repo root, in Git Bash:  bash deploy/deploy.sh
#
# First-time setup (database, .env, admin user, AI keys) is in README.md.
# reference-docs/ and *.md files are never deployed.
set -euo pipefail

HOST=Ger1-root
SITE_USER=articles
APP=/mnt/ger_hd1/www/articles/app
DOMAIN=articles.peppasoft.com
PHP=php8.4

cd "$(dirname "$0")/.."
if [ -n "$(git status --porcelain)" ]; then
  echo "Commit your changes first (the deploy uses the last commit)." >&2
  exit 1
fi

echo "== whitelist this IP on the Hetzner firewall"
curl -s "https://mon.peppasoft.com/addip?_type=s" >/dev/null
sleep 5

TMP=$(mktemp -d)
git archive --format=tar.gz -o "$TMP/server.tgz" HEAD:server
if tar tzf "$TMP/server.tgz" | grep -E '(^|/)reference-docs/|\.md$'; then
  echo "The package contains .md files or reference-docs/ - stop." >&2
  exit 1
fi

echo "== upload"
scp -q "$TMP/server.tgz" "$HOST:/tmp/articles-server.tgz"
rm -rf "$TMP"

echo "== install on server"
ssh "$HOST" bash -s <<EOF
set -euo pipefail
mkdir -p $APP
if [ -f $APP/.env ]; then
  tar czf /root/articles-backup-\$(date +%Y%m%d-%H%M%S).tgz -C $APP --exclude=vendor --exclude=storage .
fi
tar xzf /tmp/articles-server.tgz -C $APP
rm -f $APP/bootstrap/cache/packages.php $APP/bootstrap/cache/services.php /tmp/articles-server.tgz
mkdir -p $APP/storage/app/private $APP/storage/logs $APP/storage/framework/{cache,sessions,views}
chown -R $SITE_USER:$SITE_USER $APP
chmod 710 $APP
chmod 755 $APP/public
su -s /bin/bash $SITE_USER -c "cd $APP && $PHP /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction --quiet \
  && $PHP artisan migrate --force \
  && $PHP artisan db:seed --force \
  && $PHP artisan optimize"
chmod 600 $APP/.env

# Laravel scheduler: starts due articles, retries, and a queue worker that exits when the queue is empty.
echo "* * * * * $SITE_USER cd $APP && $PHP artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/articles
chmod 644 /etc/cron.d/articles

# Stop the old queue worker so the new code is used (the scheduler starts a new one within a minute).
su -s /bin/bash $SITE_USER -c "cd $APP && $PHP artisan queue:restart" >/dev/null

# Per-domain PHP-FPM opcache ignores file times: reset it through a one-shot script.
TOKEN=\$(openssl rand -hex 12)
echo '<?php opcache_reset(); echo "flushed";' > $APP/public/flush-\$TOKEN.php
chown $SITE_USER:$SITE_USER $APP/public/flush-\$TOKEN.php
curl -sk --resolve "$DOMAIN:443:162.55.167.140" "https://$DOMAIN/flush-\$TOKEN.php" -o /dev/null || true
rm -f $APP/public/flush-\$TOKEN.php
EOF

echo "== check"
for i in 1 2 3 4 5; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "https://$DOMAIN/up" || true)
  [ "$code" = "200" ] && break
  sleep 1
done
echo "https://$DOMAIN/up -> $code"
echo "https://$DOMAIN/mainadmin -> $(curl -s -o /dev/null -w '%{http_code}' "https://$DOMAIN/mainadmin")"
