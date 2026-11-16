#!/usr/bin/env bash
#
# MedFind deploy script, run ON THE SERVER (cPanel). Normally started by scripts/cron-deploy.sh
# when GitHub Actions moves the `production` branch after the tests pass.
# It can also be run by hand from cPanel Terminal:
#   bash ~/medfindsolutions.co.ke/scripts/deploy.sh production
#
# What it does, in order:
#   1. backs up the live .env (never stored in git)
#   2. puts the site in maintenance mode
#   3. fast-forwards the code to origin/<branch> (refuses anything that is not a clean fast-forward)
#   4. restores .env, backs up the database if there are new migrations, runs them
#   5. clears caches and brings the site back up
# If any step fails, the code is moved back to the previous commit and the site is brought back up.
# User files (storage/, public/uploads/, .env) are never touched by git.
set -Eeuo pipefail

BRANCH="${1:-production}"
APP_DIR="${APP_DIR:-$HOME/medfindsolutions.co.ke}"
PHP="${PHP_BIN:-php}"
STATE_DIR="$HOME/.medfind-deploy"
BACKUP_DIR="$HOME/medfind-db-backups"
KEEP_DB_BACKUPS=10

mkdir -p "$STATE_DIR" "$BACKUP_DIR"
chmod 700 "$STATE_DIR" "$BACKUP_DIR"

# Only one deploy at a time.
LOCK="$STATE_DIR/lock"
if ! mkdir "$LOCK" 2>/dev/null; then
  echo "Another deploy is running (remove $LOCK if you are sure it is not)." >&2
  exit 1
fi

cd "$APP_DIR"
[ -f .env ] || { echo "No .env in $APP_DIR — refusing to deploy." >&2; rmdir "$LOCK"; exit 1; }

PREVIOUS="$(git rev-parse HEAD)"
ENV_BACKUP="$STATE_DIR/env.$(date +%Y%m%d-%H%M%S)"
cp -a .env "$ENV_BACKUP"
chmod 600 "$ENV_BACKUP"
WENT_DOWN=0

restore_env() { cp -a "$ENV_BACKUP" .env; }

on_error() {
  local line="$1"
  echo "!! Deploy failed at line $line — rolling code back to $PREVIOUS" >&2
  rm -f "$STATE_DIR/db-client.cnf"
  restore_env
  # --keep moves back but refuses to throw away any file edited directly on the server.
  git reset --keep "$PREVIOUS" || echo "!! Automatic rollback refused; check 'git status' in $APP_DIR" >&2
  restore_env
  "$PHP" artisan optimize:clear >/dev/null 2>&1 || true
  [ "$WENT_DOWN" = 1 ] && "$PHP" artisan up || true
  echo "!! Database migrations (if any ran) were NOT reversed. Backups are in $BACKUP_DIR." >&2
  rmdir "$LOCK" 2>/dev/null || true
  exit 1
}
trap 'on_error $LINENO' ERR

echo "==> Deploying $BRANCH into $APP_DIR (currently $(git rev-parse --short HEAD))"
git fetch --quiet origin "$BRANCH"
TARGET="$(git rev-parse "origin/$BRANCH")"
if [ "$TARGET" = "$PREVIOUS" ]; then
  echo "==> Already up to date."
  git rev-parse --short=8 HEAD > storage/app/deployed-release
  rmdir "$LOCK"
  exit 0
fi

"$PHP" artisan down --retry=15 && WENT_DOWN=1

# Tracked files under storage/ and bootstrap/cache/ are only placeholders (.gitignore) and old
# scripts. Copying a live storage/ folder over a fresh clone marks them as changed, which makes
# git refuse to update. Real data there is untracked and never touched by this.
git checkout -- storage bootstrap/cache 2>/dev/null || true

# .env used to be tracked in git. Undo local edits to the tracked copy so git can update or
# remove it, then always put the live copy back afterwards.
if git ls-files --error-unmatch .env >/dev/null 2>&1; then git checkout -- .env; fi

# Switch to the deploy branch the first time (the server was set up from a feature branch).
if [ "$(git rev-parse --abbrev-ref HEAD)" != "$BRANCH" ]; then
  git checkout "$BRANCH" 2>/dev/null || git checkout -b "$BRANCH" "origin/$BRANCH"
fi
git merge --ff-only "origin/$BRANCH"
restore_env

# Back up the database before schema changes.
if "$PHP" artisan migrate:status --no-ansi 2>/dev/null | grep -q "Pending"; then
  echo "==> New migrations found — backing up the database first"
  CNF="$STATE_DIR/db-client.cnf"; : > "$CNF"; chmod 600 "$CNF"
  DB_NAME="$("$PHP" scripts/db-client-config.php "$CNF")"
  DUMP="$BACKUP_DIR/${DB_NAME}-$(date +%Y%m%d-%H%M%S)-before-$(git rev-parse --short HEAD).sql.gz"
  mysqldump --defaults-extra-file="$CNF" --single-transaction --routines --no-tablespaces "$DB_NAME" | gzip > "$DUMP"
  rm -f "$CNF"
  chmod 600 "$DUMP"
  echo "    saved $DUMP"
  ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +$((KEEP_DB_BACKUPS + 1)) | xargs -r rm -f
  "$PHP" artisan migrate --force
fi

"$PHP" artisan optimize:clear
chmod -R ug+rwX storage bootstrap/cache
"$PHP" artisan up
WENT_DOWN=0
git rev-parse --short=8 HEAD > storage/app/deployed-release   # read by GET /__release

ls -1t "$STATE_DIR"/env.* 2>/dev/null | tail -n +11 | xargs -r rm -f
echo "$PREVIOUS" > "$STATE_DIR/previous-release"
rmdir "$LOCK"
echo "==> Deployed $(git rev-parse --short HEAD): $(git log -1 --pretty=%s)"
echo "    To undo: cd $APP_DIR && git reset --keep $PREVIOUS && php artisan optimize:clear"
