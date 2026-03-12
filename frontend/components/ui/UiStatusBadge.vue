<script setup lang="ts">
const props = defineProps<{
  label: string
  variant: 'success' | 'error' | 'warning' | 'info' | 'neutral'
}>()

const variantClasses: Record<string, string> = {
  success: 'bg-green-100 text-green-700',
  error: 'bg-red-100 text-red-700',
  warning: 'bg-amber-100 text-amber-700',
  info: 'bg-blue-100 text-blue-700',
  neutral: 'bg-gray-100 text-gray-600',
}

const dotClasses: Record<string, string> = {
  success: 'bg-green-500',
  error: 'bg-red-500',
  warning: 'bg-amber-500',
  info: 'bg-blue-500',
  neutral: 'bg-gray-400',
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="variantClasses[props.variant]"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="dotClasses[props.variant]" />
    {{ props.label }}
  </span>
</template>