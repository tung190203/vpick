#!/bin/bash
set -e

echo "=============================="
echo "🚀 START LOCAL DEPLOY"
echo "=============================="

# 1. Update main
echo "▶ Checkout & pull main"
git checkout main
git pull origin main

# 2. Build DEV
echo "▶ Merge main → dev"
git checkout dev
git merge main --no-edit

echo "▶ Build DEV"
npm run build:dev

echo "▶ Commit DEV build"
git add public/build
git commit -m "build(dev): update assets" || echo "No DEV changes"
git push origin dev

# 3. Build PROD
echo "▶ Merge main → deploy"
git checkout deploy
git merge main --no-edit

echo "▶ Build PROD"
npm run build:prod

echo "▶ Commit PROD build"
git add public/build
git commit -m "build(prod): update assets" || echo "No PROD changes"
git push origin deploy

# 4. Back to main
git checkout main

echo "=============================="
echo "✅ DEPLOY FINISHED"
echo "=============================="
