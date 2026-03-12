<script setup lang="ts">
const { t } = useI18n()

useSeoMeta({
  title: t('app.name'),
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
  <main class="flex min-h-screen items-center justify-center">
    <h1 class="text-3xl font-bold text-primary-700">
      {{ t('app.name') }}
    </h1>
  </main>
</template>