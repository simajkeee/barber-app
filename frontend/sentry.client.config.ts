import * as Sentry from '@sentry/nuxt'

const runtimeConfig = useRuntimeConfig()

Sentry.init({
  dsn: runtimeConfig.public.sentryDsn || undefined,
  environment: process.env.NODE_ENV,
  tracesSampleRate: 0.1,
  integrations: [
    Sentry.browserTracingIntegration(),
  ],
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
})
