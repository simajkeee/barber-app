<script setup lang="ts">
const props = defineProps<{
  to: string
  label: string
}>()

const route = useRoute()
const localePath = useLocalePath()

const isActive = computed(() => {
  const resolved = localePath(props.to)
  return route.path === resolved
})
</script>

<template>
  <NuxtLink
    :to="localePath(props.to)"
    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
    :class="isActive
      ? 'bg-primary-50 text-primary-700'
      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
  >
    <slot name="icon" />
    {{ props.label }}
  </NuxtLink>
</template>