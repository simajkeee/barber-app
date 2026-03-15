<script setup lang="ts">
import type { TimeSlot } from '~/types/appointment'

const props = defineProps<{
  slots: TimeSlot[]
  modelValue: string | null
  isLoading: boolean
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [startTime: string | null]
}>()

const { t } = useI18n()
const { locale } = useI18n()

const labelId = useId()
const errorId = `${labelId}-error`

function formatTime(iso: string) {
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Asia/Ho_Chi_Minh',
    hour12: false,
  }).format(new Date(iso))
}
</script>

<template>
  <div>
    <p :id="labelId" class="mb-1 text-sm font-medium text-gray-700">
      {{ t('appointments.form.timeSlot') }}
      <span class="text-error" aria-hidden="true">*</span>
    </p>

    <div v-if="isLoading" class="flex items-center gap-2 py-3 text-sm text-gray-500">
      <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-primary-700" />
      <span>{{ t('appointments.form.loadingSlots') }}</span>
    </div>

    <div
      v-else-if="slots.length === 0"
      class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500"
    >
      {{ t('appointments.form.noSlots') }}
    </div>

    <div
      v-else
      class="grid grid-cols-3 gap-2 sm:grid-cols-4"
      :aria-labelledby="labelId"
      :aria-describedby="error ? errorId : undefined"
    >
      <button
        v-for="slot in slots"
        :key="slot.startTime"
        type="button"
        class="rounded-lg border px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        :class="slot.startTime === modelValue
          ? 'border-primary-700 bg-primary-700 text-white'
          : 'border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:bg-primary-50'"
        :aria-pressed="slot.startTime === modelValue"
        @click="emit('update:modelValue', slot.startTime === modelValue ? null : slot.startTime)"
      >
        {{ formatTime(slot.startTime) }}
      </button>
    </div>

    <p v-if="error" :id="errorId" class="mt-1 text-sm text-error" role="alert">{{ error }}</p>
  </div>
</template>
