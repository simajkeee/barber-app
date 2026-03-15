<script setup lang="ts">
import type { AppointmentStatus } from '~/types/appointment'

defineProps<{
  status: AppointmentStatus
}>()

const { t } = useI18n()

const variantMap: Record<AppointmentStatus, string> = {
  scheduled: 'info',
  completed: 'success',
  cancelled: 'neutral',
  no_show: 'warning',
}
</script>

<template>
  <UiStatusBadge
    :variant="variantMap[status] as any"
    :label="t(`appointments.status.${status}`)"
  />
</template>
