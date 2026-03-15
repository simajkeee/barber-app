<script setup lang="ts">
import type { Appointment, CreateAppointmentRequest } from '~/types/appointment'

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
const formLoading = ref(false)
const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)

const appointmentId = computed(() => route.params.id as string)

onMounted(async () => {
  try {
    appointment.value = await appointmentApi.getAppointment(appointmentId.value)
    if (['completed', 'cancelled', 'no_show'].includes(appointment.value.status)) {
      toast.error('appointments.error.notModifiable')
      await navigateTo(localePath(`/dashboard/appointments/${appointmentId.value}`))
    }
  } catch {
    toast.error('appointments.error.notFound')
    await navigateTo(localePath('/dashboard/appointments'))
  } finally {
    isLoading.value = false
  }
})

async function onSubmit(data: CreateAppointmentRequest) {
  formLoading.value = true
  try {
    await appointmentApi.updateAppointment(appointmentId.value, data)
    toast.success('appointments.toast.updated')
    await navigateTo(localePath(`/dashboard/appointments/${appointmentId.value}`))
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
    } else if (error === 'APPOINTMENT_NOT_MODIFIABLE') {
      toast.error('appointments.error.notModifiable')
      await navigateTo(localePath(`/dashboard/appointments/${appointmentId.value}`))
    } else {
      toast.error('appointments.toast.updateError')
    }
  } finally {
    formLoading.value = false
  }
}

function onCancel() {
  navigateTo(localePath(`/dashboard/appointments/${appointmentId.value}`))
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <DashboardPageHeader :title="t('appointments.edit.title')" />

    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <AppointmentForm
      v-else-if="appointment"
      ref="formRef"
      :appointment="appointment"
      :loading="formLoading"
      @submit="onSubmit"
      @cancel="onCancel"
    />
  </div>
</template>
