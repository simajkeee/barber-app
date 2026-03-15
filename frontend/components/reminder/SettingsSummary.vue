<script setup lang="ts">
import type { ReminderSettings } from '~/types/reminder'

defineProps<{
  settings: ReminderSettings
}>()

const { t } = useI18n()
</script>

<template>
  <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">
          {{ t('reminders.settings.summary', { days: settings.daysSinceLastVisit }) }}
        </p>
      </div>
      <NuxtLink
        :to="useLocalePath()('/dashboard/reminders/settings')"
        class="text-sm font-medium text-primary-600 hover:text-primary-700"
      >
        {{ t('reminders.settings.title') }}
      </NuxtLink>
    </div>
  </div>
</template>
