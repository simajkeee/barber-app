export default defineNuxtConfig({
  compatibilityDate: '2025-03-12',

  modules: [
    '@sentry/nuxt/module',
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss',
    '@nuxtjs/i18n',
    '@nuxtjs/sitemap',
    '@nuxtjs/robots',
    '@vite-pwa/nuxt',
    'nuxt-schema-org',
  ],

  sentry: {
    sourceMapsUploadOptions: {
      org: process.env.SENTRY_ORG,
      project: process.env.SENTRY_PROJECT,
      authToken: process.env.SENTRY_AUTH_TOKEN,
    },

    org: 'test-095',
    project: 'barber-app-frontend',
  },

  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1',
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
      ],
    },
  },

  routeRules: {
    '/vi': { redirect: { to: '/', statusCode: 301 } },
    '/vi/**': { redirect: { to: '/**', statusCode: 301 } },
    // @ts-expect-error robots is a valid routeRules option from @nuxtjs/robots
    '/dashboard/**': { ssr: false, robots: false },
    // @ts-expect-error robots is a valid routeRules option from @nuxtjs/robots
    '/en/dashboard/**': { ssr: false, robots: false },
  },

  runtimeConfig: {
    public: {
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      apiBase: process.env.NUXT_PUBLIC_API_BASE || '/api/v1',
      facebookAppId: process.env.NUXT_PUBLIC_FACEBOOK_APP_ID || '',
      turnstileSiteKey: process.env.NUXT_PUBLIC_TURNSTILE_SITE_KEY || '',
      sentryDsn: process.env.NUXT_PUBLIC_SENTRY_DSN || '',
    },
  },

  site: {
    url: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
    name: 'BarberPro',
  },

  i18n: {
    locales: [
      { code: 'vi', iso: 'vi-VN', name: 'Tiếng Việt', file: 'vi.json' },
      { code: 'en', iso: 'en-US', name: 'English', file: 'en.json' },
    ],
    defaultLocale: 'vi',
    strategy: 'prefix_except_default',
    langDir: 'locales',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_locale',
      redirectOn: 'root',
    },
  },

  sitemap: {
    xslColumns: [
      { label: 'URL', width: '65%' },
      { label: 'Last Modified', select: 'sitemap:lastmod', width: '25%' },
    ],
  },

  robots: {
    groups: [
      {
        userAgent: '*',
        allow: '/',
        disallow: ['/dashboard'],
      },
    ],
  },

  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'BarberPro',
      short_name: 'BarberPro',
      theme_color: '#1e3a5f',
      background_color: '#f9fafb',
      display: 'standalone',
      start_url: '/',
      icons: [
        { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
        { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
      ],
    },
  },

  schemaOrg: {
    identity: {
      type: 'LocalBusiness',
      name: 'BarberPro',
    },
  },

  tailwindcss: {
    cssPath: '~/assets/css/main.css',
  },

  nitro: {
    devProxy: {
      '/api': {
        target: 'http://localhost:80/api',
        changeOrigin: true,
      },
    },
  },

  devServer: {
    https: false,
  },

  devtools: { enabled: true },

  sourcemap: {
    client: 'hidden',
  },
})