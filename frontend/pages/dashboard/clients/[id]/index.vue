<script setup lang="ts">
import type { Appointment } from '~/types/appointment'
import type { Client } from '~/types/client'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const clientApi = useClientApi()
const appointmentApi = useAppointmentApi()
const toast = useToast()

const clientId = computed(() => route.params.id as string)

const { data: client } = await useAsyncData<Client | null>(
  `client-${clientId.value}`,
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

const pageTitle = computed(() =>
  client.value ? `${client.value.firstName} ${client.value.lastName}` : '',
)

const showDeleteDialog = ref(false)
const isDeleting = ref(false)

const appointments = ref<Appointment[]>([])
const isLoadingHistory = ref(false)
const historyError = ref(false)

async function loadHistory() {
  isLoadingHistory.value = true
  historyError.value = false
  try {
    const response = await appointmentApi.listAppointments({ clientId: clientId.value, limit: 50 })
    appointments.value = response.data.slice().sort(
      (a, b) => new Date(b.startTime).getTime() - new Date(a.startTime).getTime(),
    )
  } catch {
    historyError.value = true
  } finally {
    isLoadingHistory.value = false
  }
}

onMounted(() => {
  if (client.value) {
    loadHistory()
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
    <template v-if="client">
      <DashboardPageHeader :title="pageTitle">
        <template #actions>
          <UiButton variant="secondary" @click="navigateTo(localePath('/dashboard/clients'))">
            {{ t('common.back') }}
          </UiButton>
          <UiButton variant="secondary" @click="navigateTo(localePath(`/dashboard/clients/${clientId}/edit`))">
            {{ t('shop.profile.edit') }}
          </UiButton>
        </template>
      </DashboardPageHeader>

      <ClientDetailCard :client="client" />

      <section class="mt-6">
        <h2 class="mb-3 text-base font-semibold text-gray-900">{{ t('clients.history.title') }}</h2>

        <div v-if="isLoadingHistory" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-100" />
        </div>

        <div v-else-if="historyError" class="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ t('clients.history.error') }}
          <button class="ml-2 underline hover:no-underline" @click="loadHistory">{{ t('common.retry') }}</button>
        </div>

        <p v-else-if="appointments.length === 0" class="text-sm text-gray-500">
          {{ t('clients.history.empty') }}
        </p>

        <div v-else class="space-y-2">
          <NuxtLink
            v-for="appt in appointments"
            :key="appt.id"
            :to="localePath(`/dashboard/appointments/${appt.id}`)"
            class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 transition-colors hover:bg-gray-50"
          >
            <div class="flex min-w-0 flex-col gap-0.5">
              <AppointmentTimeBadge :start-time="appt.startTime" :end-time="appt.endTime" show-date />
              <span class="truncate text-sm text-gray-600">{{ appt.service.name }}</span>
            </div>
            <AppointmentStatusBadge :status="appt.status" class="ml-3 shrink-0" />
          </NuxtLink>
        </div>
      </section>

      <div class="mt-8 border-t border-red-100 pt-6">
        <p class="mb-3 text-sm font-medium text-red-600">{{ t('clients.dangerZone') }}</p>
        <UiButton variant="danger" @click="showDeleteDialog = true">
          {{ t('clients.confirm.deleteTitle') }}
        </UiButton>
      </div>

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
