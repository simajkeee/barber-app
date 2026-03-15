<script setup lang="ts">
defineProps<{
  workingHours: { openTime: string; closeTime: string } | null
}>()

const { t } = useI18n()
</script>

<template>
  <div class="mb-4 rounded-lg border bg-white px-4 py-2.5 text-sm">
    <template v-if="workingHours">
      <span class="text-gray-500">{{ t('appointments.workingHours.label', { open: workingHours.openTime, close: workingHours.closeTime }) }}</span>
    </template>
    <template v-else>
      <span class="font-medium text-amber-600">{{ t('appointments.workingHours.closed') }}</span>
    </template>
  </div>
</template>
