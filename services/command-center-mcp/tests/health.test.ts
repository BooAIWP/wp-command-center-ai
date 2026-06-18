import assert from "node:assert/strict";
import test from "node:test";
import { createRuntimeHealth } from "../src/runtime/health.js";

test("health payload exposes operational data without secrets", () => {
  const health = createRuntimeHealth({
    version: "0.1.0",
    build: "abc123",
    runtimeMode: "staging",
    uptimeSeconds: 42.9,
    nodeVersion: "v24.0.0",
  });

  assert.deepEqual(health, {
    status: "ok",
    version: "0.1.0",
    build: "abc123",
    uptimeSeconds: 42,
    runtimeMode: "staging",
    transport: "http",
    nodeVersion: "v24.0.0",
  });
  assert.equal(JSON.stringify(health).includes("token"), false);
  assert.equal(JSON.stringify(health).includes("wordpress"), false);
});
