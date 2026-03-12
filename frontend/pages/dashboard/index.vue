<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const store = useAuthStore()
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900">
      {{ t('dashboard.title') }}
    </h1>
    <p class="mt-2 text-gray-500">
      {{ store.fullName }}
    </p>
  </div>
</template>