<script setup lang="ts">
import type { PublicService } from '~/types/booking'

defineProps<{
  service: PublicService
  date: string
  time: string
  clientName: string
  clientPhone: string
  submitting: boolean
}>()

const emit = defineEmits<{
  confirm: []
  back: []
}>()

const { t, locale } = useI18n()
const { formatPrice, formatDuration } = useFormatters()

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
  <div>
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('booking.confirm.title') }}</h2>
    <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
      <div class="p-4">
        <p class="text-sm text-gray-500">{{ t('booking.confirm.service') }}</p>
        <p class="font-medium text-gray-900">{{ service.name }}</p>
        <p class="text-sm text-gray-500">{{ formatDuration(service.duration) }} · {{ formatPrice(service.price) }}</p>
      </div>
      <div class="p-4">
        <p class="text-sm text-gray-500">{{ t('booking.confirm.dateTime') }}</p>
        <p class="font-medium text-gray-900">{{ formatDisplayDate(date) }}</p>
        <p class="text-sm text-gray-500">{{ time }}</p>
      </div>
      <div class="p-4">
        <p class="text-sm text-gray-500">{{ t('booking.confirm.client') }}</p>
        <p class="font-medium text-gray-900">{{ clientName }}</p>
        <p class="text-sm text-gray-500">{{ clientPhone }}</p>
      </div>
    </div>

    <div class="mt-6 flex gap-3">
      <button
        type="button"
        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-50 transition-colors"
        :disabled="submitting"
        @click="emit('back')"
      >
        {{ t('common.back') }}
      </button>
      <button
        type="button"
        class="flex-1 bg-primary-600 text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
        :disabled="submitting"
        @click="emit('confirm')"
      >
        <span v-if="submitting" class="flex items-center justify-center gap-2">
          <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full" />
          {{ t('booking.confirm.submitting') }}
        </span>
        <span v-else>{{ t('booking.confirm.submit') }}</span>
      </button>
    </div>
  </div>
</template>
