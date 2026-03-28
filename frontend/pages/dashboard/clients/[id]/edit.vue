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

const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const saving = ref(false)

const clientId = computed(() => route.params.id as string)

const { data: client } = await useAsyncData<Client | null>(
  `client-edit-${clientId.value}`,
  async () => {
    try {
      return await clientApi.getClient(clientId.value)
    } catch {
      toast.error('clients.error.notFound')
      await navigateTo(localePath('/dashboard/clients'))
      return null
    }
  },
)

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
    <UiBreadcrumb
      :items="[
        { label: t('clients.title'), to: localePath('/dashboard/clients') },
        { label: client ? `${client.firstName} ${client.lastName}` : '...', to: localePath(`/dashboard/clients/${clientId}`) },
        { label: t('clients.edit.title') },
      ]"
    />

    <ClientForm
      v-if="client"
      ref="formRef"
      :client="client"
      :loading="saving"
      @submit="onSubmit"
      @cancel="onCancel"
    />
  </div>
</template>
