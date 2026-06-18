import type { FleetGateway } from "../ports/FleetGateway.js";
import { ResourceNotFoundError } from "../errors.js";
import type {
  FleetSite,
  FleetSitePage,
  FleetSiteQuery,
  PlatformStatus,
  SiteCapabilities,
  SiteInventory,
} from "../../domain/models.js";

export class FleetQueryService {
  public constructor(
    private readonly gateway: FleetGateway,
    private readonly version: string,
  ) {}

  public getPlatformStatus(): PlatformStatus {
    return {
      service: "wp-command-center-ai-mcp",
      version: this.version,
      wordpressBridge: this.gateway.isConfigured() ? "configured" : "unavailable",
      timestamp: new Date().toISOString(),
    };
  }

  public listSites(query: FleetSiteQuery): Promise<FleetSitePage> {
    return this.gateway.listSites(query);
  }

  public async getSite(siteId: string): Promise<FleetSite> {
    const site = await this.gateway.getSite(siteId);

    if (site === null) {
      throw new ResourceNotFoundError("Fleet site", siteId);
    }

    return site;
  }

  public async getInventory(siteId: string): Promise<SiteInventory> {
    const inventory = await this.gateway.getInventory(siteId);

    if (inventory === null) {
      throw new ResourceNotFoundError("Inventory", siteId);
    }

    return inventory;
  }

  public async getCapabilities(siteId: string): Promise<SiteCapabilities> {
    const capabilities = await this.gateway.getCapabilities(siteId);

    if (capabilities === null) {
      throw new ResourceNotFoundError("Capabilities", siteId);
    }

    return capabilities;
  }
}
