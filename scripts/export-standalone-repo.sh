#!/usr/bin/env bash
# Export this Laravel app as a fresh git repository (no parent monorepo history).
# Usage: ./scripts/export-standalone-repo.sh [DEST_DIR]
# Default DEST_DIR: ../booking-system-api-showcase (sibling of this project folder)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST="${1:-"$ROOT/../booking-system-api-showcase"}"

echo "Exporting from: $ROOT"
echo "Destination:     $DEST"

mkdir -p "$DEST"

rsync -a --delete \
  --exclude '.git/' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude '.env' \
  --exclude '.phpunit.cache/' \
  --exclude '.phpunit.result.cache' \
  --exclude 'database/*.sqlite*' \
  --exclude '.DS_Store' \
  --exclude 'storage/logs/' \
  --exclude 'storage/framework/cache/data/' \
  --exclude 'storage/framework/sessions/' \
  --exclude 'storage/framework/views/' \
  --exclude 'bootstrap/cache/*.php' \
  "$ROOT/" "$DEST/"

mkdir -p "$DEST/storage/logs" "$DEST/storage/framework/cache/data" \
  "$DEST/storage/framework/sessions" "$DEST/storage/framework/views" \
  "$DEST/bootstrap/cache"

touch "$DEST/storage/logs/.gitkeep" \
  "$DEST/storage/framework/cache/data/.gitkeep" \
  "$DEST/storage/framework/sessions/.gitkeep" \
  "$DEST/storage/framework/views/.gitkeep"

cd "$DEST"

if [[ ! -d .git ]]; then
  git init -b main
fi

git add -A

if git diff --cached --quiet; then
  echo "Nothing new to commit (export is already up to date)."
else
  if git rev-parse HEAD >/dev/null 2>&1; then
    git commit -m "chore: sync booking-system-api-showcase standalone export"
  else
    git commit -m "Initial commit: booking-system-api-showcase"
  fi
fi

echo
echo "Next steps:"
echo "  cd \"$DEST\""
echo "  cp .env.example .env && php artisan key:generate && php artisan jwt:secret"
echo "  composer install"
echo "  git remote add origin https://github.com/YOUR_USER/booking-system-api-showcase.git"
echo "  git push -u origin main"
