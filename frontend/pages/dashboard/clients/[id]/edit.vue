<script setup lang="ts">
import type { Client, UpdateClientRequest } from '~/types/client'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const clientApi = useClientApi()
const { parseApiError } = useApiError()
const toast = useToast()

const client = ref<Client | null>(null)
const isLoading = ref(true)
const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const saving = ref(false)

const clientId = computed(() => route.params.id as string)

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

async function onSubmit(data: UpdateClientRequest) {
  saving.value = true
  try {
    await clientApi.updateClient(clientId.value, data)
    toast.success('clients.toast.updated')
    await navigateTo(localePath(`/dashboard/clients/${clientId.value}`))
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        formRef.value?.setError(field, message)
      }
    } else if (error === 'PHONE_ALREADY_EXISTS') {
      formRef.value?.setError('phone', t('clients.error.phoneExists'))
    } else {
      toast.error('clients.toast.updateError')
    }
  } finally {
    saving.value = false
  }
}

function onCancel() {
  navigateTo(localePath(`/dashboard/clients/${clientId.value}`))
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <DashboardPageHeader :title="t('clients.edit.title')" />

    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <ClientForm
      v-else-if="client"
      ref="formRef"
      :client="client"
      :loading="saving"
      @submit="onSubmit"
      @cancel="onCancel"
    />
  </div>
</template>