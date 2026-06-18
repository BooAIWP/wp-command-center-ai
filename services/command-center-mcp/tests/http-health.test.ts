import assert from "node:assert/strict";
import test from "node:test";
import { FleetQueryService } from "../src/application/services/FleetQueryService.js";
import { UnavailableFleetGateway } from "../src/infrastructure/wordpress/UnavailableFleetGateway.js";
import { createHttpApplication } from "../src/transports/http.js";

test("HTTP health endpoint is public and contains the build identity", async () => {
  const app = createHttpApplication(
    new FleetQueryService(new UnavailableFleetGateway(), "0.1.0"),
    "0.1.0",
    "abc123",
    "staging",
    "not-used-by-health",
    ["https://mcp-staging.example.com"],
  );
  const listener = app.listen(0, "127.0.0.1");

  try {
    await new Promise<void>((resolve) => listener.once("listening", resolve));
    const address = listener.address();
    assert.ok(address && typeof address === "object");

    const response = await fetch(`http://127.0.0.1:${address.port}/health`);
    const payload = (await response.json()) as Record<string, unknown>;

    assert.equal(response.status, 200);
    assert.equal(response.headers.get("cache-control"), "no-store");
    assert.equal(payload.status, "ok");
    assert.equal(payload.build, "abc123");
    assert.equal(payload.runtimeMode, "staging");
  } finally {
    await new Promise<void>((resolve, reject) =>
      listener.close((error) => (error ? reject(error) : resolve())),
    );
  }
});
