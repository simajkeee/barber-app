<script setup lang="ts">
definePageMeta({
  middleware: 'guest',
})

const { t } = useI18n()
const localePath = useLocalePath()

useSeoMeta({
  title: `${t('app.name')} — ${t('landing.hero.headline')}`,
  description: t('seo.home.description'),
  ogTitle: t('app.name'),
  ogDescription: t('seo.home.description'),
  ogType: 'website',
})

useSchemaOrg([
  defineLocalBusiness({
    name: 'BarberPro',
    image: '/pwa-512x512.png',
  }),
  defineWebPage(),
])
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 antialiased">
    <!-- Header -->
    <header class="flex items-center justify-between px-6 py-4 sm:px-10">
      <UiAppLogo />
      <UiLanguageSwitcher />
    </header>

    <main>
      <!-- Hero -->
      <section class="mx-auto max-w-3xl px-6 py-20 text-center sm:py-28">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
          {{ t('landing.hero.headline') }}
        </h1>
        <p class="mt-5 text-lg text-gray-500 sm:text-xl">
          {{ t('landing.hero.subheadline') }}
        </p>
        <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
          <NuxtLink :to="localePath('/register')">
            <UiButton size="lg">{{ t('landing.hero.ctaRegister') }}</UiButton>
          </NuxtLink>
          <NuxtLink :to="localePath('/login')">
            <UiButton size="lg" variant="secondary">{{ t('landing.hero.ctaLogin') }}</UiButton>
          </NuxtLink>
        </div>
      </section>

      <!-- Features -->
      <section class="border-t bg-gray-50 px-6 py-16 sm:px-10">
        <div class="mx-auto max-w-4xl">
          <div class="grid gap-10 sm:grid-cols-3">
            <div class="text-center">
              <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <h2 class="mt-4 text-base font-semibold text-gray-900">{{ t('landing.features.bookings.title') }}</h2>
              <p class="mt-2 text-sm text-gray-500">{{ t('landing.features.bookings.description') }}</p>
            </div>

            <div class="text-center">
              <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <h2 class="mt-4 text-base font-semibold text-gray-900">{{ t('landing.features.clients.title') }}</h2>
              <p class="mt-2 text-sm text-gray-500">{{ t('landing.features.clients.description') }}</p>
            </div>

            <div class="text-center">
              <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
              </div>
              <h2 class="mt-4 text-base font-semibold text-gray-900">{{ t('landing.features.reminders.title') }}</h2>
              <p class="mt-2 text-sm text-gray-500">{{ t('landing.features.reminders.description') }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Social proof / Testimonials -->
      <section class="border-t bg-white px-6 py-16 sm:px-10">
        <div class="mx-auto max-w-4xl">
          <h2 class="mb-10 text-center text-2xl font-bold text-gray-900">
            {{ t('landing.social.title') }}
          </h2>
          <div class="grid gap-6 sm:grid-cols-3">
            <div
              v-for="n in 3"
              :key="n"
              class="rounded-xl border border-gray-200 p-6"
            >
              <p class="text-sm leading-relaxed text-gray-600">
                "{{ t(`landing.social.quote${n}`) }}"
              </p>
              <div class="mt-5 flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                  {{ t(`landing.social.author${n}`).split(' ').map((w: string) => w[0]).join('').slice(0, 2).toUpperCase() }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ t(`landing.social.author${n}`) }}</p>
                  <p class="text-xs text-gray-500">{{ t(`landing.social.shop${n}`) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="flex flex-col items-center gap-3 px-6 py-6 sm:flex-row sm:justify-between">
      <p class="text-sm text-gray-400">© {{ new Date().getFullYear() }} {{ t('app.name') }}</p>
      <div class="flex gap-4 text-sm text-gray-400">
        <NuxtLink :to="localePath('/privacy')" class="hover:text-gray-600">{{ t('footer.privacy') }}</NuxtLink>
        <NuxtLink :to="localePath('/terms')" class="hover:text-gray-600">{{ t('footer.terms') }}</NuxtLink>
      </div>
    </footer>
  </div>
</template>