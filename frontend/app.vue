<script setup lang="ts">
import { Toaster } from 'vue-sonner'
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
  <Toaster position="top-right" :duration="3000" rich-colors />
</template>