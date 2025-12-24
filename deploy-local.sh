#!/bin/bash
set -e

# ==============================
# COLORS & ICONS
# ==============================
GREEN="\033[0;32m"
RED="\033[0;31m"
YELLOW="\033[0;33m"
BLUE="\033[0;34m"
GRAY="\033[0;90m"
RESET="\033[0m"

CHECK="✔"
CROSS="✖"
ARROW="➜"
DOT="•"

step () {
  echo -e "\n${BLUE}${ARROW} $1${RESET}"
}

info () {
  echo -e "${GRAY}${DOT} $1${RESET}"
}

success () {
  echo -e "${GREEN}${CHECK} $1${RESET}"
}

error () {
  echo -e "${RED}${CROSS} $1${RESET}"
}

header () {
  echo -e "\n${GREEN}=============================="
  echo -e "🚀  LOCAL DEPLOY STARTED"
  echo -e "==============================${RESET}\n"
}

footer () {
  echo -e "\n${GREEN}=============================="
  echo -e "✅  DEPLOY FINISHED SAFELY"
  echo -e "==============================${RESET}\n"
}

# ==============================
# START
# ==============================
header

# ==============================
# SAFETY CHECK
# ==============================
step "Safety checks"

CURRENT_BRANCH=$(git branch --show-current)

if [ "$CURRENT_BRANCH" != "main" ]; then
  error "Script must be started from MAIN branch"
  info "Current branch: $CURRENT_BRANCH"
  exit 1
fi

if [ -d "public/build" ]; then
  error "public/build exists on MAIN – this should NOT happen"
  info "Please remove build files from main first"
  exit 1
fi

success "Safety checks passed"

# ==============================
# 1. UPDATE MAIN
# ==============================
step "Update MAIN branch"

info "Checkout main"
git checkout main

info "Pull latest main"
git pull origin main

success "Main branch updated"

# ==============================
# 2. BUILD DEV
# ==============================
step "Build DEV assets"

info "Checkout dev"
git checkout dev

info "Merge main → dev"
git merge main --no-edit

info "Clean old DEV build"
rm -rf public/build

info "Build DEV"
npm run build:dev

info "Commit DEV build"
git add public/build
git commit -m "build(dev): update assets" || info "No DEV changes"

info "Push DEV"
git push origin dev

success "DEV build completed"

# ==============================
# 3. BUILD PROD
# ==============================
step "Build PROD assets"

info "Checkout deploy"
git checkout deploy

info "Merge main → deploy"
git merge main --no-edit

info "Clean old PROD build"
rm -rf public/build

info "Build PROD"
npm run build:prod

info "Commit PROD build"
git add public/build
git commit -m "build(prod): update assets" || info "No PROD changes"

info "Push PROD"
git push origin deploy

success "PROD build completed"

# ==============================
# 4. BACK TO MAIN
# ==============================
step "Restore MAIN working state"

info "Checkout main"
git checkout main

info "Restore clean working tree"
git restore .
git clean -fd

info "Install composer dependencies"
composer install

footer
