<script setup lang="ts">
import type { ScheduleEntry } from '~/types/shop'

defineProps<{
  schedule: ScheduleEntry[]
}>()

const { t } = useI18n()
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b px-6 py-3">
      <h3 class="text-sm font-semibold text-gray-900">{{ t('shop.schedule.title') }}</h3>
    </div>
    <div class="divide-y">
      <div
        v-for="entry in schedule"
        :key="entry.dayOfWeek"
        class="flex items-center justify-between px-6 py-2.5 text-sm"
      >
        <span class="font-medium text-gray-700">{{ t(`shop.schedule.days.${entry.dayOfWeek}`) }}</span>
        <span v-if="entry.isOpen" class="text-gray-600">
          {{ entry.openTime }} — {{ entry.closeTime }}
        </span>
        <span v-else class="text-gray-400">{{ t('shop.schedule.closed') }}</span>
      </div>
    </div>
  </div>
</template>