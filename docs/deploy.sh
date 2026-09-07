#!/usr/bin/env bash
#
# Zero-downtime deploy — plan/phases/phase-09-hardening-release.md M9.6.
#
# Builds a brand-new release into its own directory, only swaps the
# `current` symlink once the new release is fully ready, then runs the
# migration and clears caches. Nginx/PHP-FPM should point at
# <deploy-root>/current, never at a specific release directory, so the
# symlink swap is the only moment traffic sees a change.
#
# Usage: deploy.sh <deploy-root> <git-ref>
#   deploy.sh /var/www/batu main
#
# Expects, alongside <deploy-root>:
#   <deploy-root>/shared/.env               — the real production .env
#   <deploy-root>/shared/storage            — persisted across releases
#   <deploy-root>/releases/<timestamp>      — created by this script
#   <deploy-root>/current                   — symlink this script swaps
#
# First deploy: see "First deploy" in docs/DEPLOYMENT.md — this script
# assumes shared/.env and shared/storage already exist.

set -euo pipefail

DEPLOY_ROOT="${1:?Usage: deploy.sh <deploy-root> <git-ref>}"
GIT_REF="${2:?Usage: deploy.sh <deploy-root> <git-ref>}"
RELEASE_DIR="${DEPLOY_ROOT}/releases/$(date +%Y%m%d%H%M%S)"
KEEP_RELEASES=5

echo "==> Deploying ${GIT_REF} to ${RELEASE_DIR}"
git clone --depth 1 --branch "${GIT_REF}" "$(git -C "${DEPLOY_ROOT}/current" remote get-url origin)" "${RELEASE_DIR}"
DEPLOY_SHA="$(git -C "${RELEASE_DIR}" rev-parse HEAD)"

echo "==> Linking shared resources"
ln -sfn "${DEPLOY_ROOT}/shared/.env" "${RELEASE_DIR}/.env"
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${DEPLOY_ROOT}/shared/storage" "${RELEASE_DIR}/storage"

echo "==> Installing dependencies"
cd "${RELEASE_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "==> Priming caches on the new release (not yet live)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Running migrations"
# Runs against the shared database BEFORE the symlink swap. A migration
# in this release must be additive/backward-compatible with the release
# still serving traffic — see "Rollback procedure" in docs/DEPLOYMENT.md.
php artisan migrate --force

echo "==> Swapping the symlink (this is the moment traffic sees the new release)"
ln -sfn "${RELEASE_DIR}" "${DEPLOY_ROOT}/current"

echo "==> Reloading long-running workers"
php artisan queue:restart

echo "==> Pruning old releases (keeping last ${KEEP_RELEASES})"
cd "${DEPLOY_ROOT}/releases"
ls -1t | tail -n "+$((KEEP_RELEASES + 1))" | xargs -r rm -rf

echo "==> Done. Deployed SHA: ${DEPLOY_SHA}"
# If SENTRY_LARAVEL_DSN is configured (see .env.production.example), tag
# the release to this SHA here, e.g.:
#   curl -sS -X POST "https://sentry.io/api/0/organizations/<org>/releases/" \
#     -H "Authorization: Bearer ${SENTRY_AUTH_TOKEN}" \
#     -d "{\"version\": \"${DEPLOY_SHA}\"}"
