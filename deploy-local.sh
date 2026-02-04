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
# ERROR TRAP
# ==============================
CURRENT_STEP=""

trap 'on_error $LINENO' ERR

on_error () {
  echo -e "\n${RED}==============================${RESET}"
  echo -e "${RED}${CROSS}  DEPLOY FAILED${RESET}"
  echo -e "${YELLOW}• Step:${RESET} ${CURRENT_STEP}"
  echo -e "${GRAY}• Line:${RESET} $1"
  echo -e "${GRAY}• Fix the issue and re-run the script${RESET}"
  echo -e "${RED}==============================${RESET}\n"
  exit 1
}

# ==============================
# START
# ==============================
header

# ==============================
# SAFETY CHECK
# ==============================
step "Safety checks"

CURRENT_STEP="Check current branch"
CURRENT_BRANCH=$(git branch --show-current)

if [ "$CURRENT_BRANCH" != "main" ]; then
  error "Script must be started from MAIN branch"
  info "Current branch: $CURRENT_BRANCH"
  exit 1
fi

CURRENT_STEP="Check public/build on main"
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

CURRENT_STEP="Checkout main"
git checkout main

CURRENT_STEP="Pull latest main"
git pull origin main

success "Main branch updated"

# ==============================
# 2. BUILD MATRIX
# ==============================
BUILDS=(
  "dev|build:dev|build(dev): update assets|DEV"
  "deploy|build:prod|build(prod): update assets|PROD"
  "dev1|build:dev1|build(dev1): update assets|DEV1"
)

for BUILD in "${BUILDS[@]}"; do
  IFS="|" read -r BRANCH BUILD_CMD COMMIT_MSG LABEL <<< "$BUILD"

  step "Build $LABEL assets"

  CURRENT_STEP="Checkout $BRANCH"
  git checkout "$BRANCH"

  CURRENT_STEP="Merge main → $BRANCH"
  git merge main --no-edit

  CURRENT_STEP="Clean old $LABEL build"
  rm -rf public/build

  CURRENT_STEP="Build $LABEL"
  npm run "$BUILD_CMD"

  CURRENT_STEP="Commit $LABEL build"
  git add public/build
  git commit -m "$COMMIT_MSG" || info "No $LABEL changes"

  CURRENT_STEP="Push $LABEL"
  git push origin "$BRANCH"

  success "$LABEL build completed"
done

# ==============================
# 3. BACK TO MAIN
# ==============================
step "Restore MAIN working state"

CURRENT_STEP="Checkout main"
git checkout main

CURRENT_STEP="Restore clean working tree"
git restore .
git clean -fd

CURRENT_STEP="Install composer dependencies"
composer install

footer
