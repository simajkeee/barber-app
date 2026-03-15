<script setup lang="ts">
import type { Appointment } from '~/types/appointment'

defineProps<{
  appointment: Appointment
  loading?: boolean
}>()

const emit = defineEmits<{
  view: [id: string]
  complete: [id: string]
  noShow: [id: string]
  cancel: [id: string]
}>()
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex flex-col gap-1.5 min-w-0">
        <AppointmentTimeBadge :start-time="appointment.startTime" :end-time="appointment.endTime" show-date />
        <AppointmentClientInfo :client="appointment.client" />
        <AppointmentServiceInfo :service="appointment.service" />
      </div>
      <div class="flex flex-col items-start gap-2 sm:items-end">
        <AppointmentStatusBadge :status="appointment.status" />
        <AppointmentQuickActions
          :status="appointment.status"
          :loading="loading"
          @view="emit('view', appointment.id)"
          @complete="emit('complete', appointment.id)"
          @no-show="emit('noShow', appointment.id)"
          @cancel="emit('cancel', appointment.id)"
        />
      </div>
    </div>
    <p v-if="appointment.notes" class="mt-2 text-xs text-gray-400 italic">{{ appointment.notes }}</p>
  </div>
</template>
