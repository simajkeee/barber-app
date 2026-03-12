<script setup lang="ts">
withDefaults(
  defineProps<{
    size?: 'sm' | 'md' | 'lg'
  }>(),
  { size: 'md' },
)

const { t } = useI18n()

const sizeClasses: Record<string, string> = {
  sm: 'text-lg',
  md: 'text-2xl',
  lg: 'text-3xl',
}
</script>

<template>
  <span class="font-bold tracking-tight text-primary-700" :class="sizeClasses[size]">
    {{ t('app.name') }}
  </span>
</template>