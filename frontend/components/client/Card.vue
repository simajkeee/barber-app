<script setup lang="ts">
import type { Client } from '~/types/client'

defineProps<{
  client: Client
}>()

const emit = defineEmits<{
  view: [clientId: string]
}>()

const { t } = useI18n()
const { formatDate } = useFormatters()
</script>

<template>
  <button
    type="button"
    class="w-full rounded-lg border border-gray-200 bg-white p-4 text-left transition hover:border-primary-300 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
    @click="emit('view', client.id)"
  >
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <h3 class="truncate text-sm font-medium text-gray-900">
          {{ client.firstName }} {{ client.lastName }}
        </h3>
        <p class="mt-0.5 text-sm text-gray-500">{{ client.phone }}</p>
        <p v-if="client.email" class="mt-0.5 truncate text-sm text-gray-400">{{ client.email }}</p>
      </div>
      <div class="flex shrink-0 flex-col items-end gap-1">
        <UiStatusBadge
          v-if="client.visitCount > 0"
          :label="t('clients.list.clientCount', { count: client.visitCount })"
          variant="neutral"
        />
        <span class="text-xs text-gray-400">
          {{ client.lastVisitAt ? formatDate(client.lastVisitAt) : t('clients.detail.noVisits') }}
        </span>
      </div>
    </div>
  </button>
</template>