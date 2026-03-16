<script setup lang="ts">
import type { AvailableSlot, PublicShopInfo } from '~/types/booking'

const props = defineProps<{
  shop: PublicShopInfo
  serviceId: string
  slug: string
  selectedDate: string | null
  selectedTime: string | null
}>()

const emit = defineEmits<{
  selectDate: [date: string]
  selectTime: [time: string]
}>()

const { t, locale } = useI18n()
const api = usePublicBookingApi()

const slots = ref<AvailableSlot[]>([])
const loadingSlots = ref(false)
const slotsError = ref('')

const dates = computed(() => {
  const result: { value: string; label: string; dayKey: string; closed: boolean; isToday: boolean }[] = []
  const today = new Date()

  for (let i = 0; i < 30; i++) {
    const d = new Date(today)
    d.setDate(today.getDate() + i)

    const value = d.toISOString().split('T')[0]
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
    const dayKey = dayNames[d.getDay()]
    const closed = props.shop.workingHours[dayKey] === null
    const isToday = i === 0

    const label = isToday
      ? t('booking.date.today')
      : new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
          weekday: 'short',
          month: 'short',
          day: 'numeric',
        }).format(d)

    result.push({ value, label, dayKey, closed, isToday })
  }

  return result
})

async function loadSlots(date: string) {
  loadingSlots.value = true
  slotsError.value = ''
  slots.value = []

  try {
    const response = await api.getAvailableSlots(props.slug, date, props.serviceId)
    slots.value = response.slots
  } catch {
    slotsError.value = t('booking.error.generic')
  } finally {
    loadingSlots.value = false
  }
}

function onSelectDate(date: string) {
  emit('selectDate', date)
  loadSlots(date)
}

const availableSlots = computed(() => slots.value.filter(s => s.available))
const unavailableSlots = computed(() => slots.value.filter(s => !s.available))
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('booking.steps.datetime') }}</h2>

    <!-- Date selector -->
    <div class="mb-6">
      <p class="text-sm font-medium text-gray-700 mb-2">{{ t('booking.date.select') }}</p>
      <div class="flex gap-2 overflow-x-auto pb-2">
        <button
          v-for="date in dates"
          :key="date.value"
          type="button"
          class="flex-shrink-0 px-3 py-2 rounded-lg text-sm border-2 transition-colors"
          :class="[
            date.closed
              ? 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed'
              : selectedDate === date.value
                ? 'border-primary-500 bg-primary-50 text-primary-700 font-medium'
                : 'border-gray-200 hover:border-primary-300 text-gray-700 bg-white',
          ]"
          :disabled="date.closed"
          @click="!date.closed && onSelectDate(date.value)"
        >
          <span class="block">{{ date.label }}</span>
          <span v-if="date.closed" class="block text-xs">{{ t('booking.date.closed') }}</span>
        </button>
      </div>
    </div>

    <!-- Time slot grid -->
    <div v-if="selectedDate">
      <p class="text-sm font-medium text-gray-700 mb-2">{{ t('booking.slot.select') }}</p>

      <div v-if="loadingSlots" class="text-center py-8 text-gray-500">
        <div class="animate-spin h-6 w-6 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-2" />
        {{ t('appointments.form.loadingSlots') }}
      </div>

      <div v-else-if="slotsError" class="text-center py-8 text-red-500">
        {{ slotsError }}
      </div>

      <div v-else-if="slots.length === 0" class="text-center py-8 text-gray-500">
        {{ t('booking.slot.noSlots') }}
      </div>

      <div v-else class="grid grid-cols-4 sm:grid-cols-6 gap-2">
        <button
          v-for="slot in slots"
          :key="slot.time"
          type="button"
          class="px-2 py-2 rounded-lg text-sm font-medium border-2 transition-colors"
          :class="[
            !slot.available
              ? 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed line-through'
              : selectedTime === slot.time
                ? 'border-primary-500 bg-primary-600 text-white'
                : 'border-gray-200 hover:border-primary-400 text-gray-700 bg-white',
          ]"
          :disabled="!slot.available"
          :aria-label="`${slot.time}${!slot.available ? ` - ${t('booking.slot.unavailable')}` : ''}`"
          @click="slot.available && emit('selectTime', slot.time)"
        >
          {{ slot.time }}
        </button>
      </div>
    </div>
  </div>
</template>
