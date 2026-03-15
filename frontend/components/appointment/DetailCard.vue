<script setup lang="ts">
import type { Appointment } from '~/types/appointment'

defineProps<{
  appointment: Appointment
}>()

const { t } = useI18n()
const { formatPrice, formatDuration, formatDate } = useFormatters()
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
    <div class="p-5 flex items-center justify-between gap-4">
      <div>
        <p class="text-xs text-gray-500 mb-1">{{ t('appointments.detail.status') }}</p>
        <AppointmentStatusBadge :status="appointment.status" />
      </div>
      <div class="text-right">
        <p class="text-xs text-gray-500 mb-0.5">{{ t('appointments.detail.createdAt') }}</p>
        <p class="text-sm text-gray-600">{{ formatDate(appointment.createdAt) }}</p>
      </div>
    </div>

    <div class="p-5 grid grid-cols-2 gap-5">
      <div>
        <p class="text-xs text-gray-500 mb-1">{{ t('appointments.detail.date') }}</p>
        <AppointmentTimeBadge :start-time="appointment.startTime" :end-time="appointment.endTime" show-date />
      </div>
      <div>
        <p class="text-xs text-gray-500 mb-1">{{ t('appointments.detail.service') }}</p>
        <p class="text-sm font-medium text-gray-900">{{ appointment.service.name }}</p>
        <p class="text-xs text-gray-500">{{ formatDuration(appointment.service.durationMinutes) }} · {{ formatPrice(appointment.service.price) }}</p>
      </div>
    </div>

    <div class="p-5">
      <p class="text-xs text-gray-500 mb-1">{{ t('appointments.detail.client') }}</p>
      <AppointmentClientInfo :client="appointment.client" linkable />
    </div>

    <div class="p-5">
      <p class="text-xs text-gray-500 mb-1">{{ t('appointments.detail.notes') }}</p>
      <p class="text-sm text-gray-700">
        {{ appointment.notes ?? t('appointments.detail.noNotes') }}
      </p>
    </div>
  </div>
</template>
