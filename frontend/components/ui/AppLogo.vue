<script setup lang="ts">
withDefaults(
  defineProps<{
    size?: 'sm' | 'md' | 'lg'
    to?: string
  }>(),
  { size: 'md' },
)

const { t } = useI18n()
const localePath = useLocalePath()

const sizeClasses: Record<string, string> = {
  sm: 'text-lg',
  md: 'text-2xl',
  lg: 'text-3xl',
}
</script>

<template>
  <NuxtLink
    v-if="to"
    :to="localePath(to)"
    class="font-bold tracking-tight text-primary-700"
    :class="sizeClasses[size]"
  >
    {{ t('app.name') }}
  </NuxtLink>
  <span v-else class="font-bold tracking-tight text-primary-700" :class="sizeClasses[size]">
    {{ t('app.name') }}
  </span>
</template>