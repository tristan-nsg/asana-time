import meow from "meow";
import { Asana } from "./asana.ts";

/** CLI argument parser with help text and flag definitions. */
const cli = meow(
  `
  Usage
    $ asana-time

  Options
    --workspace_gid, -w  Asana workspace GID (or set ASANA_GID env var)
    --token, -t          Asana personal access token (or set ASANA_TOKEN env var)
    --user, -u           Asana user GID or email (or set ASANA_USER env var, defaults to "me")
`,
  {
    importMeta: import.meta,
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
const WORKSPACE_GID = Deno.env.get("ASANA_GID") ?? cli.flags.workspaceGid;
/** Personal access token from env or `--token` flag. */
const ASANA_TOKEN = Deno.env.get("ASANA_TOKEN") ?? cli.flags.token;
/** User GID or email from env or `--user` flag. */
const ASANA_USER = Deno.env.get("ASANA_USER") ?? cli.flags.user;

if (import.meta.main) {
  let missingConfig = false;

  if (WORKSPACE_GID === undefined) {
    console.log(
      "WORKSPACE_GID not set via %cASANA_GID%c environment variable or %c-w%c flag",
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

  const asana = new Asana(WORKSPACE_GID!, ASANA_TOKEN!, ASANA_USER);
  console.log(JSON.stringify(await asana.timeEntries()));
}
