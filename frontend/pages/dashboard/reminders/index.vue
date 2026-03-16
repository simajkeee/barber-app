<script setup lang="ts">
import type { ReminderCandidate, ReminderSettings } from '~/types/reminder'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const reminderApi = useReminderApi()
const toast = useToast()

const candidates = ref<ReminderCandidate[]>([])
const settings = ref<ReminderSettings | null>(null)
const total = ref(0)
const cursor = ref<string | null>(null)
const isLoading = ref(true)
const isLoadingMore = ref(false)

async function loadReminders(append = false) {
  if (append) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
  }

  try {
    const response = await reminderApi.getTodayReminders({
      cursor: append ? (cursor.value ?? undefined) : undefined,
    })

    if (append) {
      candidates.value = [...candidates.value, ...response.data]
    } else {
      candidates.value = response.data
      settings.value = response.settings
    }
    total.value = response.meta.total
    cursor.value = response.meta.cursor
  } catch {
    toast.error('reminders.toast.loadError')
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

async function onMarkReminded(clientId: string) {
  try {
    await reminderApi.markReminded(clientId)
    candidates.value = candidates.value.filter((c) => c.clientId !== clientId)
    total.value = Math.max(0, total.value - 1)
    toast.success('reminders.card.reminded')
  } catch {
    toast.error('reminders.toast.markError')
  }
}

onMounted(() => loadReminders())
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('reminders.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/reminders/settings')">
          <UiButton variant="secondary">{{ t('reminders.settings.title') }}</UiButton>
        </NuxtLink>
      </template>
    </DashboardPageHeader>

    <ReminderSettingsSummary v-if="settings && !isLoading" :settings="settings" />

    <p v-if="!isLoading && total > 0" class="mb-4 text-sm text-gray-500">
      {{ t('reminders.totalCount', { count: total }) }}
    </p>

    <ReminderList
      :candidates="candidates"
      :is-loading="isLoading"
      @mark-reminded="onMarkReminded"
    />

    <ReminderListPagination
      :has-more="cursor !== null"
      :is-loading="isLoadingMore"
      @load-more="loadReminders(true)"
    />
  </div>
</template>
