import { FleetQueryService } from "./application/services/FleetQueryService.js";
import type { FleetGateway } from "./application/ports/FleetGateway.js";
import type { ServerConfig } from "./config.js";
import { UnavailableFleetGateway } from "./infrastructure/wordpress/UnavailableFleetGateway.js";
import { WordPressFleetGateway } from "./infrastructure/wordpress/WordPressFleetGateway.js";

export const SERVICE_VERSION = "0.1.0";

export function createFleetQueryService(config: ServerConfig): FleetQueryService {
  return new FleetQueryService(createFleetGateway(config), SERVICE_VERSION);
}

function createFleetGateway(config: ServerConfig): FleetGateway {
  if (
    config.wordpress.baseUrl === undefined ||
    config.wordpress.bridgeToken === undefined
  ) {
    return new UnavailableFleetGateway();
  }

  return new WordPressFleetGateway({
    baseUrl: config.wordpress.baseUrl,
    token: config.wordpress.bridgeToken,
  });
}
