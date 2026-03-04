import { Hono } from "@hono/hono";
import { logger } from "@hono/hono/logger";

if (import.meta.main) {
  const app = new Hono();
  app.use(logger());

  app.get("/", (c) => c.text("Hono!"));

  Deno.serve(app.fetch);
}
