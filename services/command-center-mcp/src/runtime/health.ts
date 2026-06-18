export interface RuntimeHealth {
  status: "ok";
  version: string;
  build: string;
  uptimeSeconds: number;
  runtimeMode: string;
  transport: "http";
  nodeVersion: string;
}

export interface RuntimeHealthContext {
  version: string;
  build: string;
  runtimeMode: string;
  uptimeSeconds?: number;
  nodeVersion?: string;
}

export function createRuntimeHealth(
  context: RuntimeHealthContext,
): RuntimeHealth {
  return {
    status: "ok",
    version: context.version,
    build: context.build,
    uptimeSeconds: Math.floor(context.uptimeSeconds ?? process.uptime()),
    runtimeMode: context.runtimeMode,
    transport: "http",
    nodeVersion: context.nodeVersion ?? process.version,
  };
}
