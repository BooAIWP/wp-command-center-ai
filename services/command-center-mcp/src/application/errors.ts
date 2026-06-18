export class ResourceNotFoundError extends Error {
  public constructor(resource: string, identifier: string) {
    super(`${resource} not found: ${identifier}`);
    this.name = "ResourceNotFoundError";
  }
}

export class GatewayUnavailableError extends Error {
  public constructor(message = "The WordPress fleet gateway is not configured.") {
    super(message);
    this.name = "GatewayUnavailableError";
  }
}
