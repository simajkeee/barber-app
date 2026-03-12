<script setup lang="ts">
defineProps<{
  text: string
  linkText: string
  to: string
}>()

const localePath = useLocalePath()
</script>

<template>
  <p class="mt-6 text-center text-sm text-gray-500">
    {{ text }}
    <NuxtLink :to="localePath(to)" class="font-medium text-primary-600 hover:text-primary-700">
      {{ linkText }}
    </NuxtLink>
  </p>
</template>