#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${WPCCAI_APP_ROOT:?WPCCAI_APP_ROOT is required}"
REPOSITORY_DIR="${WPCCAI_REPOSITORY_DIR:-$APP_ROOT/repository}"
BACKUP_DIR="${WPCCAI_BACKUP_DIR:-$APP_ROOT/backups/pre-deploy}"
BRANCH="${WPCCAI_DEPLOY_BRANCH:-main}"
SERVICE_DIR="$REPOSITORY_DIR/services/command-center-mcp"

if [ ! -d "$REPOSITORY_DIR/.git" ]; then
  printf 'Repository not found: %s\n' "$REPOSITORY_DIR" >&2
  exit 1
fi

if [ -n "$(git -C "$REPOSITORY_DIR" status --porcelain)" ]; then
  printf 'Deployment aborted: repository working tree is not clean.\n' >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"
previous_commit="$(git -C "$REPOSITORY_DIR" rev-parse HEAD)"
printf '%s\n' "$previous_commit" > "$BACKUP_DIR/previous-commit.txt"

git -C "$REPOSITORY_DIR" fetch origin "$BRANCH"
git -C "$REPOSITORY_DIR" checkout "$BRANCH"
git -C "$REPOSITORY_DIR" merge --ff-only "origin/$BRANCH"

deployed_commit="$(git -C "$REPOSITORY_DIR" rev-parse HEAD)"
cd "$SERVICE_DIR"

export WPCCAI_BUILD_ID="$deployed_commit"
npm ci
npm run check
npm audit --omit=dev

printf '%s\n' "$deployed_commit" > "$BACKUP_DIR/deployed-commit.txt"

printf 'Deployment build completed.\n'
printf 'Previous commit: %s\n' "$previous_commit"
printf 'Deployed commit: %s\n' "$deployed_commit"
printf 'Restart the application using the cPanel/Passenger control provided by the host.\n'
