#!/bin/bash
set -e

echo "=============================="
echo "🚀 START LOCAL DEPLOY"
echo "=============================="

# ==============================
# SAFETY CHECK
# ==============================
CURRENT_BRANCH=$(git branch --show-current)

if [ "$CURRENT_BRANCH" != "main" ]; then
  echo "❌ Script must be started from MAIN branch"
  echo "👉 Current branch: $CURRENT_BRANCH"
  exit 1
fi

if [ -d "public/build" ]; then
  echo "❌ public/build exists on MAIN – this should NOT happen"
  echo "👉 Please remove build files from main first"
  exit 1
fi

# ==============================
# 1. UPDATE MAIN
# ==============================
echo "▶ Checkout & pull main"
git checkout main
git pull origin main

# ==============================
# 2. BUILD DEV
# ==============================
echo "▶ Checkout dev"
git checkout dev

echo "▶ Merge main → dev"
git merge main --no-edit

echo "▶ Clean old build (DEV)"
rm -rf public/build

echo "▶ Build DEV"
npm run build:dev

echo "▶ Commit DEV build"
git add public/build
git commit -m "build(dev): update assets" || echo "ℹ️ No DEV changes"
git push origin dev

# ==============================
# 3. BUILD PROD
# ==============================
echo "▶ Checkout deploy"
git checkout deploy

echo "▶ Merge main → deploy"
git merge main --no-edit

echo "▶ Clean old build (PROD)"
rm -rf public/build

echo "▶ Build PROD"
npm run build:prod

echo "▶ Commit PROD build"
git add public/build
git commit -m "build(prod): update assets" || echo "ℹ️ No PROD changes"
git push origin deploy

# ==============================
# 4. BACK TO MAIN
# ==============================
git checkout main
git restore .
git clean -fd
echo "▶ Install composer dependencies"
composer install

echo "=============================="
echo "✅ DEPLOY FINISHED SAFELY"
echo "=============================="
