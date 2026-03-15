<script setup lang="ts">
import type { BookingResponse, PublicService } from '~/types/booking'

defineProps<{
  booking: BookingResponse
  service: PublicService
}>()

const emit = defineEmits<{
  bookAnother: []
}>()

const { t, locale } = useI18n()
const { formatDuration } = useFormatters()

function formatDisplayDate(dateStr: string): string {
  const d = new Date(dateStr + 'T00:00:00')
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(d)
}
</script>

<template>
  <div class="text-center">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900 mb-2">{{ t('booking.success.title') }}</h2>
    <p class="text-gray-600 mb-6">{{ t('booking.success.message') }}</p>

    <div class="bg-white rounded-lg border border-gray-200 p-4 text-left mb-6">
      <div class="space-y-2">
        <div class="flex justify-between">
          <span class="text-sm text-gray-500">{{ t('booking.confirm.service') }}</span>
          <span class="text-sm font-medium">{{ booking.appointment.service.name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-sm text-gray-500">{{ t('booking.confirm.dateTime') }}</span>
          <span class="text-sm font-medium">{{ formatDisplayDate(booking.appointment.date) }} · {{ booking.appointment.time }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-sm text-gray-500">{{ t('booking.steps.duration') }}</span>
          <span class="text-sm font-medium">{{ formatDuration(booking.appointment.service.duration) }}</span>
        </div>
      </div>
    </div>

    <button
      type="button"
      class="w-full border border-primary-300 text-primary-700 py-2.5 rounded-lg font-medium hover:bg-primary-50 transition-colors"
      @click="emit('bookAnother')"
    >
      {{ t('booking.success.bookAnother') }}
    </button>
  </div>
</template>
