import { GatewayUnavailableError } from "../../application/errors.js";
import type { FleetGateway } from "../../application/ports/FleetGateway.js";
import type {
  FleetSite,
  FleetSitePage,
  FleetSiteQuery,
  SiteCapabilities,
  SiteInventory,
} from "../../domain/models.js";

export class UnavailableFleetGateway implements FleetGateway {
  public isConfigured(): boolean {
    return false;
  }

  public listSites(_query: FleetSiteQuery): Promise<FleetSitePage> {
    return Promise.reject(new GatewayUnavailableError());
  }

  public getSite(_siteId: string): Promise<FleetSite | null> {
    return Promise.reject(new GatewayUnavailableError());
  }

  public getInventory(_siteId: string): Promise<SiteInventory | null> {
    return Promise.reject(new GatewayUnavailableError());
  }

  public getCapabilities(_siteId: string): Promise<SiteCapabilities | null> {
    return Promise.reject(new GatewayUnavailableError());
  }
}
