import { SERVICE_VERSION, createFleetQueryService } from "./bootstrap.js";
import { loadConfig } from "./config.js";
import { BUILD_ID } from "./generated/build.js";
import { startHttpTransport } from "./transports/http.js";
import { startStdioTransport } from "./transports/stdio.js";

async function main(): Promise<void> {
  const config = loadConfig();
  const fleetQueries = createFleetQueryService(config);

  if (config.transport === "http") {
    await startHttpTransport(
      fleetQueries,
      SERVICE_VERSION,
      BUILD_ID,
      config.runtimeMode,
      config.http,
    );
    return;
  }

  await startStdioTransport(fleetQueries, SERVICE_VERSION);
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.stack ?? error.message : String(error);
  console.error(message);
  process.exitCode = 1;
});
