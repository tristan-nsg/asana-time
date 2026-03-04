import { Hono } from "@hono/hono";

if (import.meta.main) {
  const app = new Hono();

  app.get("/", (c) => c.text("Hono!"));

  Deno.serve(app.fetch)
}
