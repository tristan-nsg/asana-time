import { configure, getConsoleSink, getLogger } from "@logtape/logtape";

await configure({
  sinks: { console: getConsoleSink() },
  loggers: [
    {
      category: ["logtape", "meta"],
      lowestLevel: "warning",
      sinks: ["console"],
    },
    {
      category: "asana-time",
      // deno-lint-ignore no-explicit-any
      lowestLevel: Deno.env.get("LOG_LEVEL") as any ?? "info",
      sinks: ["console"],
    },
  ],
});

export function logger(category: string) {
  return getLogger(["asana-time", category]);
}
