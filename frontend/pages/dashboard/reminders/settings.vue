<script setup lang="ts">
import type { ReminderSettings, UpdateReminderSettingsRequest } from '~/types/reminder'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t, locale } = useI18n()
const localePath = useLocalePath()
const reminderApi = useReminderApi()
const toast = useToast()

type Locale = 'vi' | 'en'

const activeLocale = ref<Locale>(locale.value === 'en' ? 'en' : 'vi')
const settingsCache = ref<Partial<Record<Locale, ReminderSettings>>>({})
const isLoading = ref(false)
const isSaving = ref(false)

const settings = computed(() => settingsCache.value[activeLocale.value] ?? null)

const { data: initialSettings } = await useAsyncData(
  `reminder-settings-${activeLocale.value}`,
  async () => {
    try {
      return await reminderApi.getSettings(activeLocale.value)
    } catch {
      toast.error('reminders.toast.settingsError')
      return null
    }
  },
)

if (initialSettings.value) {
  settingsCache.value[activeLocale.value] = initialSettings.value
}

async function loadSettings(loc: Locale) {
  isLoading.value = true
  try {
    settingsCache.value[loc] = await reminderApi.getSettings(loc)
  } catch {
    toast.error('reminders.toast.settingsError')
  } finally {
    isLoading.value = false
  }
}

async function switchLocale(loc: Locale) {
  activeLocale.value = loc
  if (!settingsCache.value[loc]) {
    await loadSettings(loc)
  }
}

async function onSave(data: UpdateReminderSettingsRequest) {
  isSaving.value = true
  try {
    const saved = await reminderApi.updateSettings({ ...data, locale: activeLocale.value })
    settingsCache.value[activeLocale.value] = saved
    toast.success('reminders.toast.settingsSaved')
    await navigateTo(localePath('/dashboard/reminders'))
  } catch {
    toast.error('reminders.toast.settingsError')
  } finally {
    isSaving.value = false
  }
}
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

    <!-- Locale tabs -->
    <div class="mb-6 flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 max-w-xs">
      <button
        v-for="loc in (['vi', 'en'] as const)"
        :key="loc"
        class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
        :class="activeLocale === loc
          ? 'bg-white shadow-sm text-gray-900'
          : 'text-gray-500 hover:text-gray-700'"
        @click="switchLocale(loc)"
      >
        {{ t(`reminders.settings.locale.${loc}`) }}
      </button>
    </div>

    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="h-10 w-64 rounded bg-gray-200" />
      <div class="h-24 w-full max-w-lg rounded bg-gray-200" />
    </div>

    <ReminderSettingsForm
      v-else-if="settings"
      :key="activeLocale"
      :settings="settings"
      :is-loading="isSaving"
      @save="onSave"
    />

    <div v-else class="rounded-lg border border-red-100 bg-red-50 px-4 py-3">
      <p class="text-sm text-red-700">{{ t('reminders.settings.loadError') }}</p>
      <button class="mt-2 text-sm font-medium text-red-700 underline hover:no-underline" @click="loadSettings(activeLocale)">
        {{ t('common.retry') }}
      </button>
    </div>
  </div>
</template>
