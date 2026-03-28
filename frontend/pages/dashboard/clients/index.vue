<script setup lang="ts">
import type { Client, ClientPagination } from '~/types/client'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const clientApi = useClientApi()
const toast = useToast()

const isLoading = ref(false)
const isLoadingMore = ref(false)
const search = ref('')
const sort = ref<'created_at' | 'last_visit_at' | 'last_name'>('created_at')
const direction = ref<'asc' | 'desc'>('desc')

const { data: initialData } = await useAsyncData('clients-list', () =>
  clientApi.listClients({ sort: sort.value, direction: direction.value }),
)

const clients = ref<Client[]>(initialData.value?.data ?? [])
const pagination = ref<ClientPagination>(initialData.value?.pagination ?? { nextCursor: null, hasMore: false })

async function loadClients(append = false) {
  if (append) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
  }

  try {
    const response = await clientApi.listClients({
      search: search.value || undefined,
      cursor: append ? (pagination.value.nextCursor ?? undefined) : undefined,
      sort: sort.value,
      direction: direction.value,
    })

    if (append) {
      clients.value = [...clients.value, ...response.data]
    } else {
      clients.value = response.data
    }
    pagination.value = response.pagination
  } catch {
    toast.error('clients.toast.loadError')
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

watch(search, () => loadClients())
watch([sort, direction], () => loadClients())

function onView(clientId: string) {
  navigateTo(localePath(`/dashboard/clients/${clientId}`))
}
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('clients.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/clients/create')">
          <UiButton>{{ t('clients.create.title') }}</UiButton>
        </NuxtLink>
      </template>
    </DashboardPageHeader>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex-1 sm:max-w-xs">
        <ClientSearchBar v-model="search" />
      </div>
      <ClientSortBar v-model:sort="sort" v-model:direction="direction" />
    </div>

    <ClientList
      :clients="clients"
      :is-loading="isLoading"
      :has-search-filter="!!search"
      @view="onView"
    />

    <ClientListPagination
      :has-more="pagination.hasMore"
      :is-loading="isLoadingMore"
      @load-more="loadClients(true)"
    />
  </div>
</template>
