import { logger } from "./log.ts";

/** Client for the Asana REST API. */
export class Asana {
  private log = logger("asana");

  /**
   * @param workspace - The workspace GID to query against.
   * @param token - A personal access token for authentication.
   * @param user - User GID or email. Defaults to `"me"`.
   */
  constructor(
    public readonly workspace: string,
    public readonly token: string,
    public readonly user?: string,
  ) {}

  /**
   * Fetches time tracking entries for the workspace.
   * @param from - Start date (inclusive). Defaults to today.
   * @param to - End date (inclusive). Defaults to today.
   */
  async timeEntries(
    from?: Temporal.PlainDate,
    to?: Temporal.PlainDate,
  ): Promise<TimeTrackingEntity[]> {
    const url = new URL("https://app.asana.com/api/1.0/time_tracking_entries");
    url.searchParams.set("workspace", this.workspace);

    const user = this.user ?? await this.me();
    url.searchParams.set("user", user);
    // Optional but neccessary fields
    url.searchParams.set(
      "opt_fields",
      "gid,billable_status,duration_minutes,entered_on,task.name,task.permalink_url,task.assignee.gid",
    );
    const start = from ?? Temporal.Now.plainDateISO();
    const end = to ?? Temporal.Now.plainDateISO();

    url.searchParams.set("entered_on_start_date", start.toString());
    url.searchParams.set("entered_on_end_date", end.toString());

    const results: TimeTrackingEntity[] = [];

    while (true) {
      const resp = await fetch(
        url,
        {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        },
      );

      if (!resp.ok) {
        const { message } = await resp.json();
        throw new Error(`${resp.status} ${resp.statusText}: ${message}`);
      }

      const { data, next_page } = await resp.json();
      results.push(...data);

      if (!next_page?.uri) break;
      url.searchParams.set("offset", next_page.offset);
    }

    return results;
  }

  async me(): Promise<string> {
    const resp = await fetch("https://app.asana.com/api/1.0/users/me", {
      headers: {
        Authorization: `Bearer ${this.token}`,
        "Content-Type": "application/json",
      },
    });
    if (!resp.ok) throw new Error(`${resp.status} ${resp.statusText}`);
    const { data } = await resp.json();
    // console.log(data);
    return data.gid;
  }

  /**
   * Returns the permalink URL for a given task, or `null` if not found.
   * @param task_gid - The task GID to look up.
   * @returns The permalink URL, or `null` if the task does not exist.
   * @throws If the API request fails with a non-404 error.
   */
  async taskURL(task_gid: string | number): Promise<null | string> {
    const url = new URL(`https://app.asana.com/api/1.0/tasks/${task_gid}`);
    url.searchParams.set("opt_fields", "permalink_url");

    const resp = await fetch(url, {
      headers: {
        Authorization: `Bearer ${this.token}`,
        "Content-Type": "application/json",
      },
    });

    if (!resp.ok) {
      if (resp.status === 404) {
        return null;
      }

      throw new Error(
        `Failed to fetch task ${task_gid}: ${resp.status} ${resp.statusText}`,
      );
    }

    const { data } = await resp.json();

    return data.permalink_url;
  }

  async setTimeTrackingStatus(
    entity: TimeTrackingEntity,
    status: BillableStatus,
  ): Promise<TimeTrackingEntity> {
    if (entity.billable_status === status) return entity;
    entity.billable_status = status;

    const url = new URL(
      `https://app.asana.com/api/1.0/time_tracking_entries/${entity.gid}`,
    );

    const resp = await fetch(url, {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${this.token}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        data: {
          billable_status: status,
        },
      }),
    });

    if (!resp.ok) throw new Error(`${resp.status} ${resp.statusText}`);

    return entity;
  }
}

export interface TimeTrackingEntity {
  gid: string;
  duration_minutes: number;
  task: {
    gid: string;
    assignee: {
      gid: string;
    };
    name: string;
    permalink_url: string;
  };
  entered_on: string | Temporal.PlainDate;
  billable_status: BillableStatus;
}

type BillableStatus = "billable" | "nonBillable" | "notApplicable";
// test
