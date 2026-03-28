<script setup lang="ts">
import { Toaster } from 'vue-sonner'
</script>

<template>
  <NuxtLoadingIndicator color="var(--color-primary-700)" />
  <NuxtLayout>
    <NuxtPage>
      <template #fallback>
        <div class="flex justify-center py-12">
          <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
        </div>
      </template>
    </NuxtPage>
  </NuxtLayout>
  <Toaster position="top-right" :duration="3000" rich-colors />
</template>