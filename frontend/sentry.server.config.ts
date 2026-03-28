import * as Sentry from "@sentry/nuxt";

Sentry.init({
  dsn: "https://REDACTED_SENTRY_FRONTEND_DSN",
  tracesSampleRate: 0.1,
  sendDefaultPii: false,
  enableLogs: true,
  debug: false,
});
