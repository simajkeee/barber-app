<script setup lang="ts">
import type { ReminderCandidate } from '~/types/reminder'

defineProps<{
  candidates: ReminderCandidate[]
  isLoading: boolean
}>()

const emit = defineEmits<{
  markReminded: [clientId: string]
}>()

const { t } = useI18n()
</script>

<template>
  <div>
    <template v-if="isLoading">
      <div class="space-y-4">
        <div v-for="i in 3" :key="i" class="animate-pulse rounded-lg border border-gray-200 bg-white p-4">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="h-4 w-32 rounded bg-gray-200" />
              <div class="mt-2 h-3 w-24 rounded bg-gray-200" />
            </div>
            <div class="h-5 w-16 rounded-full bg-gray-200" />
          </div>
          <div class="mt-3 h-16 rounded bg-gray-200" />
          <div class="mt-3 flex gap-2">
            <div class="h-7 w-20 rounded bg-gray-200" />
            <div class="h-7 w-14 rounded bg-gray-200" />
            <div class="h-7 w-16 rounded bg-gray-200" />
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="candidates.length === 0">
      <UiEmptyState
        :title="t('reminders.empty.title')"
        :description="t('reminders.empty.description')"
      />
    </template>

    <template v-else>
      <div class="space-y-4">
        <ReminderCard
          v-for="candidate in candidates"
          :key="candidate.clientId"
          :candidate="candidate"
          @mark-reminded="emit('markReminded', $event)"
        />
      </div>
    </template>
  </div>
</template>
