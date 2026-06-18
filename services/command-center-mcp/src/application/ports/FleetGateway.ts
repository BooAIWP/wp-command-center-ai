import type {
  FleetSite,
  FleetSitePage,
  FleetSiteQuery,
  SiteCapabilities,
  SiteInventory,
} from "../../domain/models.js";

export interface FleetGateway {
  isConfigured(): boolean;
  listSites(query: FleetSiteQuery): Promise<FleetSitePage>;
  getSite(siteId: string): Promise<FleetSite | null>;
  getInventory(siteId: string): Promise<SiteInventory | null>;
  getCapabilities(siteId: string): Promise<SiteCapabilities | null>;
}
