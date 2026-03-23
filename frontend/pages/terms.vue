<script setup lang="ts">
const { t } = useI18n()

useSeoMeta({
  title: () => `${t('legal.terms.title')} — ${t('app.name')}`,
})
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 antialiased">
    <header class="flex items-center justify-between px-6 py-4 sm:px-10">
      <UiAppLogo />
    </header>

    <main class="mx-auto max-w-2xl px-6 py-12">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('legal.terms.title') }}</h1>
      <p class="mt-6 text-gray-500">{{ t('legal.comingSoon') }}</p>
    </main>
  </div>
</template>
