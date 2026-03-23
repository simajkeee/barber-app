<script setup lang="ts">
import type { CreateAppointmentRequest } from '~/types/appointment'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const appointmentApi = useAppointmentApi()
const { parseApiError } = useApiError()
const toast = useToast()

const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const loading = ref(false)

async function onSubmit(data: CreateAppointmentRequest) {
  loading.value = true
  try {
    const appointment = await appointmentApi.createAppointment(data)
    toast.success('appointments.toast.created')
    await navigateTo(localePath(`/dashboard/appointments/${appointment.id}`))
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        formRef.value?.setError(field, message)
      }
    } else if (error === 'APPOINTMENT_OVERLAP') {
      formRef.value?.setError('_general', t('appointments.error.overlap'))
    } else if (error === 'OUTSIDE_WORKING_HOURS') {
      formRef.value?.setError('_general', t('appointments.error.outsideHours'))
    } else if (error === 'SHOP_CLOSED') {
      formRef.value?.setError('_general', t('appointments.error.shopClosed'))
    } else if (error === 'TIME_IN_PAST') {
      formRef.value?.setError('_general', t('appointments.error.timeInPast'))
    } else if (error === 'SERVICE_INACTIVE') {
      formRef.value?.setError('serviceId', t('appointments.error.serviceInactive'))
    } else if (error === 'APPOINTMENT_LIMIT_REACHED') {
      formRef.value?.setError('_general', t('subscription.usage.limitReached'))
    } else if (error === 'SUBSCRIPTION_CANCELLED') {
      formRef.value?.setError('_general', t('appointments.error.subscriptionCancelled'))
    } else {
      toast.error('appointments.toast.createError')
    }
  } finally {
    loading.value = false
  }
}

function onCancel() {
  navigateTo(localePath('/dashboard/appointments'))
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <DashboardPageHeader :title="t('appointments.create.title')" />
    <UiBreadcrumb
      :items="[
        { label: t('appointments.title'), to: localePath('/dashboard/appointments') },
        { label: t('appointments.create.title') },
      ]"
    />
    <AppointmentForm ref="formRef" :loading="loading" @submit="onSubmit" @cancel="onCancel" />
  </div>
</template>
