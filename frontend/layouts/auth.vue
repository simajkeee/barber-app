<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased">
    <div class="flex justify-end p-4">
      <UiLanguageSwitcher />
    </div>
    <main class="flex flex-1 items-center justify-center px-4 pb-12">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </main>
    <footer class="flex justify-center gap-4 px-6 py-4 text-sm text-gray-400">
      <NuxtLink :to="localePath('/privacy')" class="hover:text-gray-600">{{ t('footer.privacy') }}</NuxtLink>
      <NuxtLink :to="localePath('/terms')" class="hover:text-gray-600">{{ t('footer.terms') }}</NuxtLink>
    </footer>
  </div>
</template>