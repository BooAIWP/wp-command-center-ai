import assert from "node:assert/strict";
import test from "node:test";
import { loadConfig } from "../src/config.js";

test("stdio is the secure default transport", () => {
  const config = loadConfig({});

  assert.equal(config.transport, "stdio");
  assert.equal(config.http.host, "127.0.0.1");
});

test("HTTP transport requires a token and an origin allowlist", () => {
  assert.throws(
    () => loadConfig({ WPCCAI_MCP_TRANSPORT: "http" }),
    /BOOTSTRAP_TOKEN/,
  );

  assert.throws(
    () =>
      loadConfig({
        WPCCAI_MCP_TRANSPORT: "http",
        WPCCAI_MCP_BOOTSTRAP_TOKEN: "secret",
      }),
    /ALLOWED_ORIGINS/,
  );
});

test("cPanel runtime PORT is supported without overriding an explicit MCP port", () => {
  assert.equal(loadConfig({ PORT: "9123" }).http.port, 9123);
  assert.equal(
    loadConfig({ PORT: "9123", WPCCAI_MCP_PORT: "8787" }).http.port,
    8787,
  );
});
