<script setup lang="ts">
import type { Client } from '~/types/client'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const clientApi = useClientApi()
const toast = useToast()

const client = ref<Client | null>(null)
const isLoading = ref(true)
const showDeleteDialog = ref(false)
const isDeleting = ref(false)

const clientId = computed(() => route.params.id as string)
const pageTitle = computed(() =>
  client.value ? `${client.value.firstName} ${client.value.lastName}` : '',
)

onMounted(async () => {
  try {
    client.value = await clientApi.getClient(clientId.value)
  } catch {
    toast.error('clients.error.notFound')
    await navigateTo(localePath('/dashboard/clients'))
  } finally {
    isLoading.value = false
  }
})

async function confirmDelete() {
  isDeleting.value = true
  try {
    await clientApi.deleteClient(clientId.value)
    toast.success('clients.toast.deleted')
    await navigateTo(localePath('/dashboard/clients'))
  } catch {
    toast.error('clients.toast.deleteError')
  } finally {
    isDeleting.value = false
    showDeleteDialog.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <template v-else-if="client">
      <DashboardPageHeader :title="pageTitle">
        <template #actions>
          <UiButton variant="ghost" @click="navigateTo(localePath('/dashboard/clients'))">
            {{ t('common.back') }}
          </UiButton>
          <UiButton variant="secondary" @click="navigateTo(localePath(`/dashboard/clients/${clientId}/edit`))">
            {{ t('shop.profile.edit') }}
          </UiButton>
          <UiButton variant="danger" @click="showDeleteDialog = true">
            {{ t('clients.confirm.deleteTitle') }}
          </UiButton>
        </template>
      </DashboardPageHeader>

      <ClientDetailCard :client="client" />

      <UiConfirmDialog
        :open="showDeleteDialog"
        :title="t('clients.confirm.deleteTitle')"
        :description="t('clients.confirm.deleteDescription')"
        variant="danger"
        :loading="isDeleting"
        @confirm="confirmDelete"
        @cancel="showDeleteDialog = false"
      />
    </template>
  </div>
</template>