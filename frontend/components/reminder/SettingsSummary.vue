<script setup lang="ts">
import type { ReminderSettings } from '~/types/reminder'

defineProps<{
  settings: ReminderSettings
}>()

const { t } = useI18n()
const localePath = useLocalePath()
</script>

<template>
  <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between gap-4">
      <div class="min-w-0">
        <p class="text-sm text-gray-500">
          {{ t('reminders.settings.summary', { days: settings.daysSinceLastVisit }) }}
        </p>
        <p class="mt-0.5 truncate text-xs text-gray-400" :title="settings.messageTemplate">
          {{ settings.messageTemplate }}
        </p>
      </div>
      <NuxtLink
        :to="localePath('/dashboard/reminders/settings')"
        class="shrink-0 text-sm font-medium text-primary-600 hover:text-primary-700"
      >
        {{ t('reminders.settings.title') }}
      </NuxtLink>
    </div>
  </div>
</template>
