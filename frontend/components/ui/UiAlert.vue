<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    type?: 'error' | 'success' | 'warning' | 'info'
    message: string
  }>(),
  { type: 'error' },
)

const styleMap: Record<string, string> = {
  error: 'bg-error-light text-error border-error/20',
  success: 'bg-success-light text-success border-success/20',
  warning: 'bg-warning-light text-warning border-warning/20',
  info: 'bg-info-light text-info border-info/20',
}
</script>

<template>
  <div
    role="alert"
    aria-live="polite"
    class="rounded-lg border px-4 py-3 text-sm"
    :class="styleMap[props.type]"
  >
    {{ props.message }}
  </div>
</template>