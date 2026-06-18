export interface CatalogEntry {
  name: string;
  title: string;
  description: string;
  access: "read";
}

export const toolCatalog: readonly CatalogEntry[] = [
  {
    name: "platform_get_status",
    title: "Platform status",
    description: "Returns MCP service and WordPress bridge availability.",
    access: "read",
  },
  {
    name: "fleet_list_sites",
    title: "List fleet sites",
    description: "Lists registered WordPress sites with optional fleet filters.",
    access: "read",
  },
  {
    name: "fleet_get_site",
    title: "Get fleet site",
    description: "Returns normalized metadata and status for one fleet site.",
    access: "read",
  },
  {
    name: "inventory_get_site",
    title: "Get site inventory",
    description: "Returns the latest normalized inventory snapshot for a site.",
    access: "read",
  },
  {
    name: "capabilities_get_site",
    title: "Get site capabilities",
    description: "Returns the latest negotiated capabilities for a site.",
    access: "read",
  },
] as const;

export const resourceCatalog: readonly CatalogEntry[] = [
  {
    name: "wpccai://platform/architecture",
    title: "Platform architecture",
    description: "Describes the external control-plane boundary.",
    access: "read",
  },
  {
    name: "wpccai://fleet/summary",
    title: "Fleet summary",
    description: "Provides a compact summary of currently visible fleet sites.",
    access: "read",
  },
] as const;
