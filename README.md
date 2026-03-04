# Asana-Time

A command line tool for automated time tracking management in Asana. It marks
all of your time entries for today as non-billable.

## Install

Download the latest binary for your platform from the
[install page](https://sidequest.sbs/asana-time) or from
[GitHub Releases](https://github.com/tristan-nsg/asana-time/releases/latest).

| Platform | Architecture  | File                            |
| -------- | ------------- | ------------------------------- |
| Windows  | x86_64        | `asana-time_windows-x86_64.exe` |
| Linux    | x86_64        | `asana-time_linux-x86_64`       |
| Linux    | ARM64         | `asana-time_linux-aarch64`      |
| macOS    | Apple Silicon | `asana-time_mac-aarch64`        |
| macOS    | Intel         | `asana-time_mac-x86_64`         |

After downloading, move the binary somewhere on your `PATH` and make it
executable (macOS/Linux):

```sh
chmod +x asana-time*
sudo mv asana-time* /usr/local/bin/asana-time
```

## Configure

### Get your Asana credentials

You'll need three pieces of information from Asana:

1. **Personal Access Token** — Go to
   [Asana Developer Console](https://app.asana.com/0/developer-console), click
   **Create new token**, give it a name, and copy the token.

2. **Workspace GID** — The easiest way to find this is to open Asana in your
   browser and look at the URL. It's the long number in the URL, e.g.
   `https://app.asana.com/0/66597537713722/...` — the workspace GID is
   `66597537713722`.

3. **User GID** _(optional)_ — Your user GID can be found in your Asana profile
   settings URL. If you skip this, the tool defaults to the user associated with
   your token.

### Set your credentials

Add these lines to your shell profile so the credentials are always available.

**Mac / Linux** — Add to `~/.bashrc`, `~/.zshrc`, or equivalent:

```sh
export ASANA_TOKEN="your_personal_access_token_here"
export ASANA_WORKSPACE=your_workspace_gid_here
export ASANA_USER=your_user_gid_here
```

**Windows (PowerShell)** — Add to your
[PowerShell profile](https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.core/about/about_profiles):

```powershell
$env:ASANA_TOKEN = "your_personal_access_token_here"
$env:ASANA_WORKSPACE = "your_workspace_gid_here"
$env:ASANA_USER = "your_user_gid_here"
```

After saving, close and reopen your terminal for the changes to take effect.

## Run

```sh
asana-time
```

The tool will fetch all of your time entries for today and mark them as
non-billable.

You can also pass credentials as flags instead of environment variables:

```sh
asana-time -w <workspace_gid> -t <token> -u <user_gid>
```

## Development

### Prerequisites

1. [Install Deno](https://docs.deno.com/runtime/getting_started/installation/)
2. Clone the repository:
   ```sh
   git clone https://github.com/tristan-nsg/asana-time.git
   cd asana-time
   ```
3. Create a `.env` file in the project root with your credentials:
   ```
   ASANA_TOKEN="your_personal_access_token_here"
   ASANA_WORKSPACE=your_workspace_gid_here
   ASANA_USER=your_user_gid_here
   ```

### Running

| Command           | Description                                       |
| ----------------- | ------------------------------------------------- |
| `deno task dev`   | Run with file watching — auto-restarts on changes |
| `deno task start` | Single run                                        |

### Project structure

```
src/
├── main.ts        Entry point and CLI argument parsing
├── asana.ts       Asana REST API client
├── log.ts         Structured logger configuration
└── main_test.ts   Tests
```

### Architecture

The tool is built on three modules:

**`main.ts`** — Parses CLI flags using [`@std/cli`](https://jsr.io/@std/cli),
resolves configuration from environment variables or flags, validates that
required config is present, and orchestrates the main loop: fetch today's time
entries, then mark each one as non-billable.

**`asana.ts`** — A client class wrapping the
[Asana REST API v1.0](https://developers.asana.com/reference/rest-api-reference).
Handles authentication (Bearer token), pagination, and exposes methods for:

- `timeEntries(from?, to?)` — Fetch time tracking entries for the configured
  user and workspace. Defaults to today. Uses the
  [Temporal API](https://tc39.es/proposal-temporal/docs/) for dates.
- `me()` — Resolve the authenticated user's GID.
- `taskURL(task_gid)` — Get a task's permalink URL.
- `setTimeTrackingStatus(entity, status)` — Update a time entry's billable
  status (`"billable"`, `"nonBillable"`, or `"notApplicable"`).

**`log.ts`** — Configures [LogTape](https://logtape.org/) with a console sink.
Log level is controlled by the `LOG_LEVEL` environment variable (defaults to
`info`). Loggers are namespaced under `["asana-time", category]`.

### Environment variables

| Variable          | Required | Description                                              |
| ----------------- | -------- | -------------------------------------------------------- |
| `ASANA_TOKEN`     | Yes      | Asana personal access token                              |
| `ASANA_WORKSPACE` | Yes      | Asana workspace GID                                      |
| `ASANA_USER`      | No       | User GID or email (defaults to `"me"`)                   |
| `LOG_LEVEL`       | No       | `debug`, `info`, `warning`, `error` (defaults to `info`) |

### Permissions

The Deno tasks use explicit permissions instead of `--allow-all`:

| Flag                                                           | Reason                                       |
| -------------------------------------------------------------- | -------------------------------------------- |
| `--allow-env=ASANA_TOKEN,ASANA_WORKSPACE,ASANA_USER,LOG_LEVEL` | Read credentials and config from environment |
| `--allow-net=app.asana.com`                                    | Make API requests to Asana                   |
| `--unstable-temporal`                                          | Enable the Temporal date API                 |
| `--env-file`                                                   | Load variables from `.env`                   |

### Building

Compiled binaries for all platforms can be built with:

```sh
deno task build
```

Binaries are output to the `dist/` directory.

### Dependencies

| Package                                           | Source | Purpose              |
| ------------------------------------------------- | ------ | -------------------- |
| [@std/cli](https://jsr.io/@std/cli) `^1.0.28`     | JSR    | CLI argument parsing |
| [@logtape/logtape](https://logtape.org/) `^2.0.4` | JSR    | Structured logging   |
| [@std/assert](https://jsr.io/@std/assert) `1`     | JSR    | Test assertions      |
