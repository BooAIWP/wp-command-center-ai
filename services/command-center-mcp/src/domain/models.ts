export type SiteStatus = "online" | "offline" | "unknown";

export interface PlatformStatus {
  service: string;
  version: string;
  wordpressBridge: "configured" | "unavailable";
  timestamp: string;
}

export interface FleetSite {
  id: string;
  name: string;
  url: string;
  status: SiteStatus;
  groups: string[];
  tags: string[];
  wordpressVersion?: string;
  phpVersion?: string;
  lastSeenAt?: string;
}

export interface FleetSiteQuery {
  status?: SiteStatus;
  group?: string;
  tag?: string;
  search?: string;
  cursor?: string;
  limit: number;
}

export interface FleetSitePage {
  items: FleetSite[];
  nextCursor?: string;
}

export interface InventoryComponent {
  slug: string;
  name: string;
  version: string;
  status?: string;
}

export interface SiteInventory {
  siteId: string;
  collectedAt: string;
  wordpress: {
    version: string;
    multisite: boolean;
  };
  runtime: {
    phpVersion: string;
    databaseVersion?: string;
  };
  plugins: InventoryComponent[];
  themes: InventoryComponent[];
}

export interface NegotiatedCapability {
  name: string;
  version: string;
  enabled: boolean;
  attributes: Record<string, string | number | boolean>;
}

export interface SiteCapabilities {
  siteId: string;
  negotiatedAt: string;
  capabilities: NegotiatedCapability[];
}
