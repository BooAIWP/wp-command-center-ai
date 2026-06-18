import { execFileSync } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const scriptDirectory = dirname(fileURLToPath(import.meta.url));
const serviceDirectory = resolve(scriptDirectory, "..");
const outputDirectory = resolve(serviceDirectory, "src", "generated");
const outputFile = resolve(outputDirectory, "build.ts");

const buildId = process.env.WPCCAI_BUILD_ID || resolveGitCommit() || "unknown";

mkdirSync(outputDirectory, { recursive: true });
writeFileSync(
  outputFile,
  `// Generated during build. Do not edit.\nexport const BUILD_ID = ${JSON.stringify(buildId)};\n`,
  "utf8",
);

function resolveGitCommit() {
  try {
    return execFileSync("git", ["rev-parse", "--short=12", "HEAD"], {
      cwd: serviceDirectory,
      encoding: "utf8",
      stdio: ["ignore", "pipe", "ignore"],
    }).trim();
  } catch {
    return "";
  }
}
