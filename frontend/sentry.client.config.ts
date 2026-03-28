import * as Sentry from "@sentry/nuxt";

Sentry.init({
  dsn: "https://REDACTED_SENTRY_FRONTEND_DSN",
  tracesSampleRate: 0.1,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
  integrations: [Sentry.replayIntegration()],
  sendDefaultPii: false,
  enableLogs: true,
  debug: false,
  ignoreErrors: [
    // Handled fetch/API errors — not bugs
    'FetchError',
    // Navigation guard duplicates — not bugs
    'NavigationDuplicated',
    // Network-level failures (offline, CORS, etc.) — not actionable
    'Failed to fetch',
    'Load failed',
    'NetworkError',
  ],
});
