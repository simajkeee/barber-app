<script setup lang="ts">
import type { Appointment } from '~/types/appointment'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const appointmentApi = useAppointmentApi()
const { parseApiError } = useApiError()
const toast = useToast()

const appointment = ref<Appointment | null>(null)
const isLoading = ref(true)

const appointmentId = computed(() => route.params.id as string)
const isTerminal = computed(() =>
  appointment.value
    ? ['completed', 'cancelled', 'no_show'].includes(appointment.value.status)
    : false,
)

const pageTitle = computed(() => {
  if (!appointment.value) return ''
  const c = appointment.value.client
  return `${c.firstName} ${c.lastName} – ${appointment.value.service.name}`
})

onMounted(async () => {
  try {
    appointment.value = await appointmentApi.getAppointment(appointmentId.value)
  } catch {
    toast.error('appointments.error.notFound')
    await navigateTo(localePath('/dashboard/appointments'))
  } finally {
    isLoading.value = false
  }
})

// Status action confirm dialog
type ActionType = 'complete' | 'noShow' | 'cancel'
const confirmOpen = ref(false)
const pendingAction = ref<ActionType | null>(null)
const actionLoading = ref(false)

function promptAction(type: ActionType) {
  pendingAction.value = type
  confirmOpen.value = true
}

async function confirmAction() {
  if (!pendingAction.value || !appointment.value) return
  actionLoading.value = true
  const type = pendingAction.value
  try {
    if (type === 'cancel') {
      appointment.value = await appointmentApi.cancelAppointment(appointmentId.value)
      toast.success('appointments.toast.cancelled')
    } else if (type === 'complete') {
      appointment.value = await appointmentApi.changeStatus(appointmentId.value, 'completed')
      toast.success('appointments.toast.completed')
    } else {
      appointment.value = await appointmentApi.changeStatus(appointmentId.value, 'no_show')
      toast.success('appointments.toast.noShow')
    }
    confirmOpen.value = false
    pendingAction.value = null
  } catch (err) {
    const { error } = parseApiError(err)
    if (error === 'APPOINTMENT_NOT_MODIFIABLE') {
      toast.error('appointments.error.notModifiable')
    } else {
      toast.error('appointments.toast.statusError')
    }
  } finally {
    actionLoading.value = false
  }
}

const confirmTitle = computed(() => {
  if (!pendingAction.value) return ''
  const map = { complete: 'completeTitle', noShow: 'noShowTitle', cancel: 'cancelTitle' }
  return t(`appointments.confirm.${map[pendingAction.value]}`)
})

const confirmDescription = computed(() => {
  if (!pendingAction.value) return ''
  const map = { complete: 'completeDescription', noShow: 'noShowDescription', cancel: 'cancelDescription' }
  return t(`appointments.confirm.${map[pendingAction.value]}`)
})
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <template v-else-if="appointment">
      <DashboardPageHeader :title="pageTitle">
        <template #actions>
          <UiButton variant="secondary" @click="navigateTo(localePath('/dashboard/appointments'))">
            {{ t('common.back') }}
          </UiButton>
          <UiButton
            v-if="!isTerminal"
            variant="secondary"
            @click="navigateTo(localePath(`/dashboard/appointments/${appointmentId}/edit`))"
          >
            {{ t('appointments.actions.edit') }}
          </UiButton>
          <template v-if="appointment.status === 'scheduled'">
            <UiButton variant="primary" @click="promptAction('complete')">
              {{ t('appointments.actions.complete') }}
            </UiButton>
            <UiButton variant="secondary" @click="promptAction('noShow')">
              {{ t('appointments.actions.noShow') }}
            </UiButton>
            <UiButton variant="danger" @click="promptAction('cancel')">
              {{ t('appointments.actions.cancel') }}
            </UiButton>
          </template>
          <NuxtLink
            v-if="appointment.status === 'completed'"
            :to="localePath(`/dashboard/appointments/create?clientId=${appointment.client.id}&serviceId=${appointment.service.id}`)"
          >
            <UiButton variant="secondary">{{ t('appointments.actions.bookAgain') }}</UiButton>
          </NuxtLink>
        </template>
      </DashboardPageHeader>

      <AppointmentDetailCard :appointment="appointment" />
    </template>

    <UiConfirmDialog
      :open="confirmOpen"
      :title="confirmTitle"
      :description="confirmDescription"
      :loading="actionLoading"
      :variant="pendingAction === 'cancel' ? 'danger' : 'primary'"
      @confirm="confirmAction"
      @cancel="confirmOpen = false"
    />
  </div>
</template>
