<script setup lang="ts">
import type { AppointmentStatus } from '~/types/appointment'

const model = defineModel<AppointmentStatus[]>({ default: () => [] })

const { t } = useI18n()

const statuses: AppointmentStatus[] = ['scheduled', 'completed', 'cancelled', 'no_show']

function toggle(status: AppointmentStatus) {
  if (model.value.includes(status)) {
    model.value = model.value.filter((s) => s !== status)
  } else {
    model.value = [...model.value, status]
  }
}
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="status in statuses"
      :key="status"
      type="button"
      class="rounded-full border px-3 py-1 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :class="model.includes(status)
        ? 'border-primary-700 bg-primary-50 text-primary-700'
        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'"
      @click="toggle(status)"
    >
      {{ t(`appointments.status.${status}`) }}
    </button>
  </div>
</template>
