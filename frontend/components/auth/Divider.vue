<script setup lang="ts">
const { t } = useI18n()
</script>

<template>
  <div class="relative my-6">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-200" />
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="bg-white px-4 text-gray-500">{{ t('auth.divider') }}</span>
    </div>
  </div>
</template>