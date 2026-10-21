#!/usr/bin/env bash
# Builds the app for shared hosting (Hostinger), which has no Node.js: production PHP packages,
# the built frontend and the generated route helpers. Run from the repository root or in CI.
#   bash deploy/hostinger/build-bundle.sh --dir <folder>   built files into <folder> (CI: hostinger-dist)
set -euo pipefail

[ "${1:-}" = "--dir" ] || { echo "usage: $0 --dir <folder>"; exit 1; }
OUT_DIR="${2:?--dir needs a folder}"

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "→ Copying the tracked source…"
git -C "$ROOT" archive --format=tar HEAD | tar -x -C "$WORK/"
cd "$WORK"

echo "→ PHP packages (production only)…"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
find vendor -type d \( -name .git -o -name .github -o -name tests -o -name docs \) -prune -exec rm -rf {} +

echo "→ Route helpers and the frontend build…"
cp .env.example .env
php artisan key:generate --force >/dev/null
php artisan wayfinder:generate --with-form >/dev/null
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules .env public/hot bootstrap/cache/*.php
echo "$(git -C "$ROOT" rev-parse --short HEAD) $(date -u +%Y-%m-%dT%H:%M:%SZ)" > REVISION

mkdir -p "$OUT_DIR"
# Keep the target's .git; replace everything else with the new build.
find "$OUT_DIR" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp -a . "$OUT_DIR/"
rm -rf "$OUT_DIR/tests" "$OUT_DIR/.github"
cp "$ROOT/deploy/hostinger/dist.gitignore" "$OUT_DIR/.gitignore"
echo "Built into $OUT_DIR"
