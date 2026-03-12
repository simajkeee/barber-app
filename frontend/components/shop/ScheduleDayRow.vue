<script setup lang="ts">
import type { DayOfWeek, ScheduleEntryForm } from '~/types/shop'

defineProps<{
  dayOfWeek: DayOfWeek
  error?: string
}>()

const model = defineModel<ScheduleEntryForm>({ required: true })

const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col gap-2 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-end sm:gap-4">
    <div class="w-28 shrink-0 sm:pb-2">
      <span class="text-sm font-medium text-gray-700">{{ t(`shop.schedule.days.${dayOfWeek}`) }}</span>
    </div>

    <div class="sm:pb-2 sm:mr-4">
      <UiToggle
        v-model="model.isOpen"
        :label="model.isOpen ? t('shop.schedule.open') : t('shop.schedule.closed')"
      />
    </div>

    <div :class="model.isOpen ? '' : 'invisible'" class="flex gap-4">
      <UiTimeInput
        v-model="model.openTime"
        :label="t('shop.schedule.openTime')"
      />
      <UiTimeInput
        v-model="model.closeTime"
        :label="t('shop.schedule.closeTime')"
      />
    </div>

    <p v-if="error" class="text-xs text-error" role="alert">{{ error }}</p>
  </div>
</template>