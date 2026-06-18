const API_ROOT = "/wp-json/wp-command-center-ai/v2";

export const WordPressApiPaths = {
  platformStatus: `${API_ROOT}/platform/status`,
  fleetSites: `${API_ROOT}/fleet/sites`,
  fleetSite: (siteId: string): string =>
    `${API_ROOT}/fleet/sites/${encodeURIComponent(siteId)}`,
  inventory: (siteId: string): string =>
    `${API_ROOT}/fleet/sites/${encodeURIComponent(siteId)}/inventory`,
  capabilities: (siteId: string): string =>
    `${API_ROOT}/fleet/sites/${encodeURIComponent(siteId)}/capabilities`,
} as const;
