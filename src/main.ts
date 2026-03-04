import meow from "meow";
import { Asana } from "./asana.ts";
import { logger } from "./log.ts";

/** CLI argument parser with help text and flag definitions. */
const cli = meow(
  `
  Usage
    $ asana-time

  Options
    --workspace_gid, -w  Asana workspace GID (or set ASANA_WORKSPACE env var)
    --token, -t          Asana personal access token (or set ASANA_TOKEN env var)
    --user, -u           Asana user GID or email (or set ASANA_USER env var, defaults to "me")
    --version, -v        Show version number
`,
  {
    importMeta: import.meta,
    version: `v0.0.1 (${Deno.build.os}/${Deno.build.arch})`,
    flags: {
      workspace_gid: {
        type: "string",
        shortFlag: "w",
      },
      token: {
        type: "string",
        shortFlag: "t",
      },
      user: {
        type: "string",
        shortFlag: "u",
      },
    },
  },
);

/** Workspace GID from env or `--workspace_gid` flag. */
const ASANA_WORKSPACE = Deno.env.get("ASANA_WORKSPACE") ?? cli.flags.workspaceGid;
/** Personal access token from env or `--token` flag. */
const ASANA_TOKEN = Deno.env.get("ASANA_TOKEN") ?? cli.flags.token;
/** User GID or email from env or `--user` flag. */
const ASANA_USER = Deno.env.get("ASANA_USER") ?? cli.flags.user;

if (import.meta.main) {
  let missingConfig = false;
  const log = logger("main");

  if (ASANA_WORKSPACE === undefined) {
    console.log(
      "ASANA_WORKSPACE not set via %cASANA_WORKSPACE%c environment variable or %c-w%c flag",
      "color: white;",
      "",
      "color: #93ccea;",
      "",
    );
    missingConfig = true;
  }

  if (ASANA_TOKEN === undefined) {
    console.log(
      "ASANA_TOKEN not set via %cASANA_TOKEN%c environment variable or %c-t%c flag",
      "color: white;",
      "",
      "color: #93ccea;",
      "",
    );
    missingConfig = true;
  }

  if (missingConfig) {
    Deno.exit(1);
  }

  const asana = new Asana(ASANA_WORKSPACE!, ASANA_TOKEN!, ASANA_USER);
  for (const entry of await asana.timeEntries()) {
    log.debug("Processing time entry {gid} for task {task}.", {
      gid: entry.gid,
      task: entry.task.name,
    });
    await asana.setTimeTrackingStatus(entry, "nonBillable");
  }
}
