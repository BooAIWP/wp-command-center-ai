import assert from "node:assert/strict";
import test from "node:test";
import { WordPressFleetGateway } from "../src/infrastructure/wordpress/WordPressFleetGateway.js";

test("WordPress gateway uses the isolated v2 read boundary", async () => {
  let requestedUrl = "";
  let authorization = "";
  const gateway = new WordPressFleetGateway({
    baseUrl: "https://command-center.example.com/",
    token: "machine-token",
    fetchImplementation: async (input, init) => {
      requestedUrl = String(input);
      authorization = new Headers(init?.headers).get("authorization") ?? "";

      return Response.json({ items: [] });
    },
  });

  await gateway.listSites({ limit: 50, status: "online", tag: "production" });

  assert.match(requestedUrl, /\/wp-json\/wp-command-center-ai\/v2\/fleet\/sites\?/);
  assert.match(requestedUrl, /status=online/);
  assert.match(requestedUrl, /tag=production/);
  assert.equal(authorization, "Bearer machine-token");
});
