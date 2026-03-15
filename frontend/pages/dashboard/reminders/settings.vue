<script setup lang="ts">
import type { ReminderSettings, UpdateReminderSettingsRequest } from '~/types/reminder'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const reminderApi = useReminderApi()
const toast = useToast()

const settings = ref<ReminderSettings | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)

async function loadSettings() {
  isLoading.value = true
  try {
    settings.value = await reminderApi.getSettings()
  } catch {
    toast.error('reminders.toast.settingsError')
  } finally {
    isLoading.value = false
  }
}

async function onSave(data: UpdateReminderSettingsRequest) {
  isSaving.value = true
  try {
    settings.value = await reminderApi.updateSettings(data)
    toast.success('reminders.toast.settingsSaved')
    await navigateTo(localePath('/dashboard/reminders'))
  } catch {
    toast.error('reminders.toast.settingsError')
  } finally {
    isSaving.value = false
  }
}

onMounted(() => loadSettings())
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('reminders.settings.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/reminders')">
          <UiButton variant="secondary">{{ t('common.back') }}</UiButton>
        </NuxtLink>
      </template>
    </DashboardPageHeader>

    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="h-10 w-64 rounded bg-gray-200" />
      <div class="h-24 w-full max-w-lg rounded bg-gray-200" />
    </div>

    <ReminderSettingsForm
      v-else-if="settings"
      :settings="settings"
      :is-loading="isSaving"
      @save="onSave"
    />
  </div>
</template>
