<script setup lang="ts">
import type { Client } from '~/types/client'

defineProps<{
  client: Client
}>()

const { t } = useI18n()
const { formatDate } = useFormatters()
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <dl class="grid gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.phone') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ client.phone }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.email') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">
          {{ client.email || t('clients.detail.noEmail') }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.visitCount') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ client.visitCount }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.lastVisitAt') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">
          {{ client.lastVisitAt ? formatDate(client.lastVisitAt) : t('clients.detail.noVisits') }}
        </dd>
      </div>
      <div v-if="client.notes" class="sm:col-span-2">
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.notes') }}</dt>
        <dd class="mt-0.5 whitespace-pre-line text-sm text-gray-900">{{ client.notes }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.createdAt') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ formatDate(client.createdAt) }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('clients.detail.updatedAt') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ formatDate(client.updatedAt) }}</dd>
      </div>
    </dl>
  </div>
</template>