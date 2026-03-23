<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <div class="mb-8 text-center">
    <UiAppLogo size="lg" to="/" />
    <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ title }}</h1>
  </div>
</template>