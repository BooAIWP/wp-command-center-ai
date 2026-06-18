#!/usr/bin/env bash
set -uo pipefail

failures=0
warnings=0

pass() { printf 'PASS  %s\n' "$1"; }
warn() { printf 'WARN  %s\n' "$1"; warnings=$((warnings + 1)); }
fail() { printf 'FAIL  %s\n' "$1"; failures=$((failures + 1)); }

check_command() {
  if command -v "$1" >/dev/null 2>&1; then
    pass "$1: $("$1" "$2" 2>&1 | head -n 1)"
  else
    fail "$1 is not available"
  fi
}

check_command node --version
check_command npm --version
check_command git --version
check_command ssh -V
check_command php --version
check_command composer --version

if command -v node >/dev/null 2>&1; then
  node_major="$(node -p "Number(process.versions.node.split('.')[0])" 2>/dev/null || printf '0')"
  if [ "$node_major" -ge 24 ]; then
    pass "Node.js major version is supported: $node_major"
  else
    fail "Node.js 24 or newer is required; found major version $node_major"
  fi
fi

if command -v crontab >/dev/null 2>&1; then
  pass "cron command is available"
else
  warn "cron command is unavailable"
fi

if command -v passenger-config >/dev/null 2>&1; then
  pass "Passenger is available: $(passenger-config --version 2>&1 | head -n 1)"
else
  warn "Passenger was not detected; verify cPanel Node.js App support"
fi

for variable in \
  NODE_ENV \
  WPCCAI_MCP_TRANSPORT \
  WPCCAI_MCP_ALLOWED_ORIGINS \
  WPCCAI_MCP_BOOTSTRAP_TOKEN
do
  if [ -n "${!variable:-}" ]; then
    pass "environment variable is set: $variable"
  else
    fail "required environment variable is missing: $variable"
  fi
done

if [ "${WPCCAI_MCP_TRANSPORT:-}" != "http" ]; then
  fail "staging requires WPCCAI_MCP_TRANSPORT=http"
fi

if [ "${WPCCAI_MCP_ALLOWED_ORIGINS:-}" != "${WPCCAI_MCP_ALLOWED_ORIGINS#https://}" ]; then
  pass "allowed origin uses HTTPS"
else
  fail "WPCCAI_MCP_ALLOWED_ORIGINS must start with https://"
fi

for directory in "${WPCCAI_SHARED_DIR:-}" "${WPCCAI_LOG_DIR:-}" "${WPCCAI_BACKUP_DIR:-}"
do
  if [ -z "$directory" ]; then
    warn "a runtime directory variable is not set"
  elif [ -d "$directory" ] && [ -w "$directory" ]; then
    pass "directory is writable: $directory"
  else
    fail "directory is missing or not writable: $directory"
  fi
done

if [ -n "${WPCCAI_HEALTH_URL:-}" ]; then
  if command -v curl >/dev/null 2>&1 && curl --fail --silent --show-error \
    --max-time 10 "${WPCCAI_HEALTH_URL%/}/health" >/dev/null; then
    pass "HTTPS health endpoint is reachable"
  else
    warn "health endpoint is not reachable yet"
  fi
else
  warn "WPCCAI_HEALTH_URL is not set; HTTPS probe skipped"
fi

if [ -f package.json ] && [ -f package-lock.json ]; then
  if npm run build >/dev/null; then
    pass "MCP service build succeeded"
  else
    fail "MCP service build failed"
  fi
else
  fail "run this script from services/command-center-mcp"
fi

printf '\nReadiness result: %s failure(s), %s warning(s)\n' "$failures" "$warnings"
exit "$failures"
