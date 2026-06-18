import type { FleetGateway } from "../../application/ports/FleetGateway.js";
import type {
  FleetSite,
  FleetSitePage,
  FleetSiteQuery,
  SiteCapabilities,
  SiteInventory,
} from "../../domain/models.js";
import { WordPressApiPaths } from "./WordPressApiPaths.js";

export interface WordPressFleetGatewayOptions {
  baseUrl: string;
  token: string;
  timeoutMs?: number;
  fetchImplementation?: typeof fetch;
}

export class WordPressFleetGateway implements FleetGateway {
  private readonly baseUrl: string;
  private readonly token: string;
  private readonly timeoutMs: number;
  private readonly fetchImplementation: typeof fetch;

  public constructor(options: WordPressFleetGatewayOptions) {
    this.baseUrl = options.baseUrl.replace(/\/+$/, "");
    this.token = options.token;
    this.timeoutMs = options.timeoutMs ?? 10_000;
    this.fetchImplementation = options.fetchImplementation ?? fetch;
  }

  public isConfigured(): boolean {
    return true;
  }

  public listSites(query: FleetSiteQuery): Promise<FleetSitePage> {
    const parameters = new URLSearchParams({ limit: String(query.limit) });

    for (const [key, value] of Object.entries(query)) {
      if (key !== "limit" && value !== undefined) {
        parameters.set(key, String(value));
      }
    }

    return this.request<FleetSitePage>(
      `${WordPressApiPaths.fleetSites}?${parameters.toString()}`,
    );
  }

  public getSite(siteId: string): Promise<FleetSite | null> {
    return this.requestNullable<FleetSite>(WordPressApiPaths.fleetSite(siteId));
  }

  public getInventory(siteId: string): Promise<SiteInventory | null> {
    return this.requestNullable<SiteInventory>(WordPressApiPaths.inventory(siteId));
  }

  public getCapabilities(siteId: string): Promise<SiteCapabilities | null> {
    return this.requestNullable<SiteCapabilities>(
      WordPressApiPaths.capabilities(siteId),
    );
  }

  private async requestNullable<T>(path: string): Promise<T | null> {
    const response = await this.fetch(path);

    if (response.status === 404) {
      return null;
    }

    return this.parse<T>(response);
  }

  private async request<T>(path: string): Promise<T> {
    return this.parse<T>(await this.fetch(path));
  }

  private fetch(path: string): Promise<Response> {
    return this.fetchImplementation(`${this.baseUrl}${path}`, {
      headers: {
        accept: "application/json",
        authorization: `Bearer ${this.token}`,
        "user-agent": "WP-Command-Center-AI-MCP/0.1",
      },
      method: "GET",
      signal: AbortSignal.timeout(this.timeoutMs),
    });
  }

  private async parse<T>(response: Response): Promise<T> {
    if (!response.ok) {
      throw new Error(
        `WordPress bridge request failed with HTTP ${response.status}.`,
      );
    }

    return (await response.json()) as T;
  }
}
