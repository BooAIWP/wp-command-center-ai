import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { FleetQueryService } from "../application/services/FleetQueryService.js";

const architectureResource = {
  service: "WP Command Center AI MCP Server",
  role: "External control plane and future automation brain",
  boundary:
    "MCP adapters call application services; application services depend on gateway ports; WordPress is an infrastructure adapter.",
  currentAccess: "Read-only fleet, inventory, and capability queries",
  excluded: ["job dispatch", "bulk mutations", "scheduler", "AI orchestration"],
};

export function createMcpServer(
  fleetQueries: FleetQueryService,
  version: string,
): McpServer {
  const server = new McpServer({
    name: "wp-command-center-ai",
    version,
  });

  server.registerTool(
    "platform_get_status",
    {
      title: "Platform status",
      description: "Returns MCP service and WordPress bridge availability.",
      inputSchema: {},
    },
    async () => result(fleetQueries.getPlatformStatus()),
  );

  server.registerTool(
    "fleet_list_sites",
    {
      title: "List fleet sites",
      description: "Lists registered WordPress sites with optional fleet filters.",
      inputSchema: {
        status: z.enum(["online", "offline", "unknown"]).optional(),
        group: z.string().min(1).optional(),
        tag: z.string().min(1).optional(),
        search: z.string().min(1).optional(),
        cursor: z.string().min(1).optional(),
        limit: z.number().int().min(1).max(200).default(50),
      },
    },
    async (input) => result(await fleetQueries.listSites(input)),
  );

  server.registerTool(
    "fleet_get_site",
    {
      title: "Get fleet site",
      description: "Returns normalized metadata and status for one fleet site.",
      inputSchema: {
        siteId: z.string().min(1),
      },
    },
    async ({ siteId }) => result(await fleetQueries.getSite(siteId)),
  );

  server.registerTool(
    "inventory_get_site",
    {
      title: "Get site inventory",
      description: "Returns the latest normalized inventory snapshot for a site.",
      inputSchema: {
        siteId: z.string().min(1),
      },
    },
    async ({ siteId }) => result(await fleetQueries.getInventory(siteId)),
  );

  server.registerTool(
    "capabilities_get_site",
    {
      title: "Get site capabilities",
      description: "Returns the latest negotiated capabilities for a site.",
      inputSchema: {
        siteId: z.string().min(1),
      },
    },
    async ({ siteId }) => result(await fleetQueries.getCapabilities(siteId)),
  );

  server.registerResource(
    "platform-architecture",
    "wpccai://platform/architecture",
    {
      title: "Platform architecture",
      description: "Describes the external control-plane boundary.",
      mimeType: "application/json",
    },
    async (uri) => ({
      contents: [
        {
          uri: uri.href,
          mimeType: "application/json",
          text: JSON.stringify(architectureResource, null, 2),
        },
      ],
    }),
  );

  server.registerResource(
    "fleet-summary",
    "wpccai://fleet/summary",
    {
      title: "Fleet summary",
      description: "Provides a compact summary of currently visible fleet sites.",
      mimeType: "application/json",
    },
    async (uri) => {
      const page = await fleetQueries.listSites({ limit: 200 });
      const summary = {
        visibleSites: page.items.length,
        online: page.items.filter((site) => site.status === "online").length,
        offline: page.items.filter((site) => site.status === "offline").length,
        unknown: page.items.filter((site) => site.status === "unknown").length,
        truncated: page.nextCursor !== undefined,
      };

      return {
        contents: [
          {
            uri: uri.href,
            mimeType: "application/json",
            text: JSON.stringify(summary, null, 2),
          },
        ],
      };
    },
  );

  return server;
}

function result(value: unknown) {
  return {
    content: [
      {
        type: "text" as const,
        text: JSON.stringify(value, null, 2),
      },
    ],
    structuredContent: value as Record<string, unknown>,
  };
}
