<script setup lang="ts">
import type { CreateClientRequest } from '~/types/client'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const clientApi = useClientApi()
const { parseApiError } = useApiError()
const toast = useToast()

const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const loading = ref(false)

async function onSubmit(data: CreateClientRequest) {
  loading.value = true
  try {
    const client = await clientApi.createClient(data)
    toast.success('clients.toast.created')
    await navigateTo(localePath(`/dashboard/clients/${client.id}`))
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        formRef.value?.setError(field, message)
      }
    } else if (error === 'PHONE_ALREADY_EXISTS') {
      formRef.value?.setError('phone', t('clients.error.phoneExists'))
    } else {
      toast.error('clients.toast.createError')
    }
  } finally {
    loading.value = false
  }
}

function onCancel() {
  navigateTo(localePath('/dashboard/clients'))
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <DashboardPageHeader :title="t('clients.create.title')" />
    <ClientForm ref="formRef" :loading="loading" @submit="onSubmit" @cancel="onCancel" />
  </div>
</template>