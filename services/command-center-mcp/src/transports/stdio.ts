import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import type { FleetQueryService } from "../application/services/FleetQueryService.js";
import { createMcpServer } from "../mcp/createServer.js";

export async function startStdioTransport(
  fleetQueries: FleetQueryService,
  version: string,
): Promise<void> {
  const server = createMcpServer(fleetQueries, version);
  const transport = new StdioServerTransport();

  await server.connect(transport);
}
