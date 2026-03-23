<script setup lang="ts">
defineProps<{
  items: Array<{ label: string; to?: string }>
}>()
</script>

<template>
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-gray-500">
      <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1">
        <span v-if="index > 0" aria-hidden="true" class="text-gray-400">›</span>
        <NuxtLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="hover:text-gray-700"
        >
          {{ item.label }}
        </NuxtLink>
        <span v-else class="text-gray-900">{{ item.label }}</span>
      </li>
    </ol>
  </nav>
</template>
