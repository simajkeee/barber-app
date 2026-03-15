<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    startTime: string
    endTime: string
    showDate?: boolean
  }>(),
  { showDate: false },
)

const { locale } = useI18n()

const tz = 'Asia/Ho_Chi_Minh'

const timeFormat = computed(() => {
  const start = new Date(props.startTime)
  const end = new Date(props.endTime)
  const fmt = new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: tz,
    hour12: false,
  })
  return `${fmt.format(start)} – ${fmt.format(end)}`
})

const dateLabel = computed(() => {
  if (!props.showDate) return null
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    day: 'numeric',
    month: 'short',
    timeZone: tz,
  }).format(new Date(props.startTime))
})
</script>

<template>
  <span class="text-sm text-gray-700">
    <span v-if="dateLabel" class="font-medium">{{ dateLabel }} · </span>
    <span class="font-medium">{{ timeFormat }}</span>
  </span>
</template>
