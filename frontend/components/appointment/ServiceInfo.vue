<script setup lang="ts">
import type { AppointmentService } from '~/types/appointment'

defineProps<{
  service: AppointmentService
}>()

const { t } = useI18n()
const { formatPrice, formatDuration } = useFormatters()
</script>

<template>
  <div class="min-w-0">
    <p class="text-sm font-medium text-gray-900 truncate">{{ service.name }}</p>
    <p class="text-xs text-gray-500">
      {{ formatDuration(service.durationMinutes) }} · {{ formatPrice(service.price) }}
    </p>
  </div>
</template>
