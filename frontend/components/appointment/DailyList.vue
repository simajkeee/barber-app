<script setup lang="ts">
import type { Appointment } from '~/types/appointment'

defineProps<{
  appointments: Appointment[]
  isLoading: boolean
  workingHours: { openTime: string; closeTime: string } | null
}>()

const emit = defineEmits<{
  view: [id: string]
  complete: [id: string]
  noShow: [id: string]
  cancel: [id: string]
}>()

const { t } = useI18n()
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <template v-else>
      <template v-if="workingHours === null">
        <UiEmptyState
          :title="t('appointments.daily.closed')"
          :description="t('appointments.daily.closedDescription')"
        />
      </template>

      <template v-else-if="appointments.length === 0">
        <UiEmptyState
          :title="t('appointments.daily.empty')"
          :description="t('appointments.daily.emptyDescription')"
        />
      </template>

      <div v-else class="space-y-3">
        <AppointmentDailyCard
          v-for="appointment in appointments"
          :key="appointment.id"
          :appointment="appointment"
          @view="emit('view', $event)"
          @complete="emit('complete', $event)"
          @no-show="emit('noShow', $event)"
          @cancel="emit('cancel', $event)"
        />
      </div>
    </template>
  </div>
</template>
