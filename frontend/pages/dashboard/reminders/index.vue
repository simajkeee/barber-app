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

const { data: initialData, error: loadError } = await useAsyncData(
  'reminders-today',
  () => reminderApi.getTodayReminders({}),
)

const candidates = ref<ReminderCandidate[]>(initialData.value?.data ?? [])
const settings = ref<ReminderSettings | null>(initialData.value?.settings ?? null)
const total = ref(initialData.value?.meta.total ?? 0)
const cursor = ref<string | null>(initialData.value?.meta.cursor ?? null)
const isLoadingMore = ref(false)

if (loadError.value) {
  toast.error('reminders.toast.loadError')
}

async function loadMore() {
  if (!cursor.value) return
  isLoadingMore.value = true
  try {
    const response = await reminderApi.getTodayReminders({ cursor: cursor.value })
    candidates.value = [...candidates.value, ...response.data]
    total.value = response.meta.total
    cursor.value = response.meta.cursor
  } catch {
    toast.error('reminders.toast.loadError')
  } finally {
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

    <ReminderSettingsSummary v-if="settings" :settings="settings" />

    <p v-if="total > 0" class="mb-4 text-sm text-gray-500">
      {{ t('reminders.totalCount', total) }}
    </p>

    <ReminderList
      :candidates="candidates"
      :is-loading="false"
      @mark-reminded="onMarkReminded"
    />

    <ReminderListPagination
      :has-more="cursor !== null"
      :is-loading="isLoadingMore"
      @load-more="loadMore"
    />
  </div>
</template>
