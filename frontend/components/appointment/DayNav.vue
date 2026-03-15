<script setup lang="ts">
const props = defineProps<{
  date: string
}>()

const emit = defineEmits<{
  'update:date': [date: string]
}>()

const { locale } = useI18n()

const dateLabel = computed(() => {
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(new Date(props.date + 'T00:00:00'))
})

function shiftDay(delta: number) {
  const d = new Date(props.date + 'T00:00:00')
  d.setDate(d.getDate() + delta)
  emit('update:date', d.toISOString().slice(0, 10))
}

function onDateInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  if (value) emit('update:date', value)
}
</script>

<template>
  <div class="mb-4 flex items-center gap-3">
    <button
      type="button"
      class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :aria-label="$t('appointments.daily.prevDay')"
      @click="shiftDay(-1)"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <div class="flex flex-1 items-center gap-2">
      <span class="text-sm font-medium text-gray-900">{{ dateLabel }}</span>
      <input
        type="date"
        :value="date"
        class="rounded border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        :aria-label="$t('appointments.daily.jumpToDate')"
        @input="onDateInput"
      />
    </div>

    <button
      type="button"
      class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :aria-label="$t('appointments.daily.nextDay')"
      @click="shiftDay(1)"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
</template>
