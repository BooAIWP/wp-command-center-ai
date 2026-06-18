export type TransportMode = "stdio" | "http";

export interface ServerConfig {
  transport: TransportMode;
  http: {
    host: string;
    port: number;
    allowedOrigins: string[];
    bootstrapToken?: string;
  };
  wordpress: {
    baseUrl?: string;
    bridgeToken?: string;
  };
}

export function loadConfig(environment: NodeJS.ProcessEnv = process.env): ServerConfig {
  const transport = environment.WPCCAI_MCP_TRANSPORT ?? "stdio";

  if (transport !== "stdio" && transport !== "http") {
    throw new Error("WPCCAI_MCP_TRANSPORT must be either stdio or http.");
  }

  const port = Number.parseInt(
    environment.WPCCAI_MCP_PORT ?? environment.PORT ?? "8787",
    10,
  );

  if (!Number.isInteger(port) || port < 1 || port > 65_535) {
    throw new Error("WPCCAI_MCP_PORT must be a valid TCP port.");
  }

  const config: ServerConfig = {
    transport,
    http: {
      host: environment.WPCCAI_MCP_HOST ?? "127.0.0.1",
      port,
      allowedOrigins: splitList(environment.WPCCAI_MCP_ALLOWED_ORIGINS),
      bootstrapToken: environment.WPCCAI_MCP_BOOTSTRAP_TOKEN,
    },
    wordpress: {
      baseUrl: environment.WPCCAI_WORDPRESS_BASE_URL,
      bridgeToken: environment.WPCCAI_WORDPRESS_BRIDGE_TOKEN,
    },
  };

  if (transport === "http") {
    if (!config.http.bootstrapToken) {
      throw new Error("HTTP transport requires WPCCAI_MCP_BOOTSTRAP_TOKEN.");
    }

    if (config.http.allowedOrigins.length === 0) {
      throw new Error("HTTP transport requires WPCCAI_MCP_ALLOWED_ORIGINS.");
    }
  }

  return config;
}

function splitList(value: string | undefined): string[] {
  return value
    ? value
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean)
    : [];
}
