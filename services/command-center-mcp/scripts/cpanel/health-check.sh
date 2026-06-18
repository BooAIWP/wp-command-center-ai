#!/usr/bin/env bash
set -euo pipefail

HEALTH_URL="${WPCCAI_HEALTH_URL:?WPCCAI_HEALTH_URL is required}"
response="$(curl --fail --silent --show-error --max-time 10 \
  "${HEALTH_URL%/}/health")"

node -e '
const payload = JSON.parse(process.argv[1]);
const fields = ["status", "version", "build", "uptimeSeconds", "runtimeMode", "transport", "nodeVersion"];
for (const field of fields) {
  if (!(field in payload)) throw new Error(`Missing health field: ${field}`);
}
if (payload.status !== "ok") throw new Error("Runtime status is not ok");
if (payload.transport !== "http") throw new Error("Unexpected transport");
console.log(JSON.stringify(payload, null, 2));
' "$response"
