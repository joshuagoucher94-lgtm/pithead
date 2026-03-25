#!/usr/bin/env sh
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/astro"
npm run build
OUT="$ROOT/deploy"
rm -rf "$OUT"
mkdir -p "$OUT"
cp -R "$ROOT/astro/dist/"* "$OUT/"
cp -R "$ROOT/php/public/"* "$OUT/"
# Glob above omits dotfiles on some shells — ensure Apache rules ship
cp "$ROOT/php/public/.htaccess" "$OUT/.htaccess" 2>/dev/null || true
echo "Deploy bundle ready at $OUT — upload contents to Hostinger public_html (merge overwrite is OK for shared assets)."
