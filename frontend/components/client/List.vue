<script setup lang="ts">
import type { Client } from '~/types/client'

defineProps<{
  clients: Client[]
  isLoading?: boolean
  hasSearchFilter?: boolean
}>()

const emit = defineEmits<{
  view: [clientId: string]
}>()

const { t } = useI18n()
const localePath = useLocalePath()
</script>

<template>
  <div>
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-start justify-between">
          <div class="flex-1 space-y-2">
            <div class="h-4 w-32 rounded bg-gray-200" />
            <div class="h-3 w-24 rounded bg-gray-100" />
          </div>
          <div class="h-5 w-16 rounded bg-gray-100" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <template v-else-if="clients.length === 0">
      <UiEmptyState
        v-if="hasSearchFilter"
        :title="t('clients.list.noResults')"
        :description="t('clients.list.noResultsDescription')"
      />
      <UiEmptyState
        v-else
        :title="t('clients.list.empty')"
        :description="t('clients.list.emptyDescription')"
      >
        <template #action>
          <NuxtLink :to="localePath('/dashboard/clients/create')">
            <UiButton>{{ t('clients.create.title') }}</UiButton>
          </NuxtLink>
        </template>
      </UiEmptyState>
    </template>

    <!-- Client list -->
    <div v-else class="space-y-3">
      <ClientCard
        v-for="client in clients"
        :key="client.id"
        :client="client"
        @view="emit('view', $event)"
      />
    </div>
  </div>
</template>