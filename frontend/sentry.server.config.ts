import * as Sentry from "@sentry/nuxt";

const { public: { sentryDsn } } = useRuntimeConfig();

Sentry.init({
  dsn: sentryDsn,
  tracesSampleRate: 0.1,
  sendDefaultPii: false,
  enableLogs: true,
  debug: false,
});