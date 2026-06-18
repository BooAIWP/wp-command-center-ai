import { randomUUID, timingSafeEqual } from "node:crypto";
import express, { type NextFunction, type Request, type Response } from "express";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { isInitializeRequest } from "@modelcontextprotocol/sdk/types.js";
import type { FleetQueryService } from "../application/services/FleetQueryService.js";
import type { ServerConfig } from "../config.js";
import { createRuntimeHealth } from "../runtime/health.js";
import { createMcpServer } from "../mcp/createServer.js";

export async function startHttpTransport(
  fleetQueries: FleetQueryService,
  version: string,
  build: string,
  runtimeMode: string,
  config: ServerConfig["http"],
): Promise<void> {
  const bootstrapToken = config.bootstrapToken;

  if (bootstrapToken === undefined) {
    throw new Error("HTTP bootstrap token is required.");
  }

  const app = createHttpApplication(
    fleetQueries,
    version,
    build,
    runtimeMode,
    bootstrapToken,
    config.allowedOrigins,
  );

  await new Promise<void>((resolve, reject) => {
    const listener = app.listen(config.port, config.host, () => {
      console.error(
        `WP Command Center AI MCP listening on http://${config.host}:${config.port}/mcp`,
      );
      resolve();
    });
    listener.on("error", reject);
  });
}

export function createHttpApplication(
  fleetQueries: FleetQueryService,
  version: string,
  build: string,
  runtimeMode: string,
  bootstrapToken: string,
  allowedOrigins: string[],
) {
  const app = express();
  const transports = new Map<string, StreamableHTTPServerTransport>();

  app.use(express.json({ limit: "1mb" }));
  app.get("/health", (_request, response) => {
    response
      .status(200)
      .setHeader("Cache-Control", "no-store")
      .json(createRuntimeHealth({ version, build, runtimeMode }));
  });
  app.use((request, response, next) =>
    authorize(request, response, next, bootstrapToken, allowedOrigins),
  );

  app.post("/mcp", async (request, response) => {
    const sessionId = request.header("mcp-session-id");
    let transport = sessionId ? transports.get(sessionId) : undefined;

    if (transport === undefined && isInitializeRequest(request.body)) {
      transport = new StreamableHTTPServerTransport({
        sessionIdGenerator: randomUUID,
        onsessioninitialized: (newSessionId) => {
          transports.set(newSessionId, transport!);
        },
      });

      transport.onclose = () => {
        if (transport?.sessionId) {
          transports.delete(transport.sessionId);
        }
      };

      await createMcpServer(fleetQueries, version).connect(transport);
    }

    if (transport === undefined) {
      response.status(400).json({
        jsonrpc: "2.0",
        error: { code: -32000, message: "Invalid or missing MCP session." },
        id: null,
      });
      return;
    }

    await transport.handleRequest(request, response, request.body);
  });

  app.get("/mcp", async (request, response) => {
    await handleExistingSession(request, response, transports);
  });

  app.delete("/mcp", async (request, response) => {
    await handleExistingSession(request, response, transports);
  });

  return app;
}

async function handleExistingSession(
  request: Request,
  response: Response,
  transports: Map<string, StreamableHTTPServerTransport>,
): Promise<void> {
  const sessionId = request.header("mcp-session-id");
  const transport = sessionId ? transports.get(sessionId) : undefined;

  if (transport === undefined) {
    response.status(400).send("Invalid or missing MCP session.");
    return;
  }

  await transport.handleRequest(request, response);
}

function authorize(
  request: Request,
  response: Response,
  next: NextFunction,
  expectedToken: string,
  allowedOrigins: string[],
): void {
  const origin = request.header("origin");

  if (origin !== undefined && !allowedOrigins.includes(origin)) {
    response.status(403).send("Origin is not allowed.");
    return;
  }

  const authorization = request.header("authorization");
  const suppliedToken = authorization?.startsWith("Bearer ")
    ? authorization.slice(7)
    : "";

  if (!secureEquals(suppliedToken, expectedToken)) {
    response.status(401).setHeader("WWW-Authenticate", "Bearer").send("Unauthorized.");
    return;
  }

  next();
}

function secureEquals(left: string, right: string): boolean {
  const leftBuffer = Buffer.from(left);
  const rightBuffer = Buffer.from(right);

  return (
    leftBuffer.length === rightBuffer.length &&
    timingSafeEqual(leftBuffer, rightBuffer)
  );
}
