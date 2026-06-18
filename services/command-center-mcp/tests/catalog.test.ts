import assert from "node:assert/strict";
import test from "node:test";
import { resourceCatalog, toolCatalog } from "../src/mcp/catalog.js";

test("MCP catalog names are unique and read-only", () => {
  const entries = [...toolCatalog, ...resourceCatalog];
  const names = entries.map((entry) => entry.name);

  assert.equal(new Set(names).size, names.length);
  assert.ok(entries.every((entry) => entry.access === "read"));
});

test("MCP scaffold does not expose job or mutation tools", () => {
  const names = toolCatalog.map((entry) => entry.name);

  assert.ok(names.every((name) => !name.includes("job")));
  assert.ok(names.every((name) => !name.includes("update")));
  assert.ok(names.every((name) => !name.includes("delete")));
});
