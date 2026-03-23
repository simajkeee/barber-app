<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { appointmentSchema } from '~/schemas/appointment'
import type { Appointment, CreateAppointmentRequest, TimeSlot } from '~/types/appointment'
import type { Client } from '~/types/client'
import type { ShopService } from '~/types/shop'

const props = withDefaults(
  defineProps<{
    appointment?: Appointment
    loading?: boolean
  }>(),
  { appointment: undefined, loading: false },
)

const emit = defineEmits<{
  submit: [data: CreateAppointmentRequest]
  cancel: []
}>()

const { t } = useI18n()
const route = useRoute()
const appointmentApi = useAppointmentApi()
const shopApi = useShopApi()
const clientApi = useClientApi()

const clients = ref<Client[]>([])
const services = ref<ShopService[]>([])
const slots = ref<TimeSlot[]>([])
const isFetchingSlots = ref(false)
const slotsError = ref<string | null>(null)

const generalError = ref<string | null>(null)

const { handleSubmit, setFieldError, setFieldValue } = useForm({
  validationSchema: toTypedSchema(appointmentSchema),
  initialValues: {
    clientId: props.appointment?.client.id ?? (typeof route.query.clientId === 'string' ? route.query.clientId : '') ?? '',
    serviceId: props.appointment?.service.id ?? (typeof route.query.serviceId === 'string' ? route.query.serviceId : '') ?? '',
    startTime: props.appointment?.startTime ?? '',
    notes: props.appointment?.notes ?? '',
  },
})

const { value: clientId, errorMessage: clientIdError } = useField<string>('clientId')
const { value: serviceId, errorMessage: serviceIdError } = useField<string>('serviceId')
const { value: startTime, errorMessage: startTimeError } = useField<string>('startTime')
const { value: notes, errorMessage: notesError } = useField<string>('notes')

// date is separate from startTime — user picks date then a slot sets startTime
const selectedDate = ref(
  props.appointment ? props.appointment.startTime.slice(0, 10) : '',
)

onMounted(async () => {
  const [clientsResponse, servicesResponse] = await Promise.all([
    clientApi.listClients({ limit: 200 }).catch(() => null),
    shopApi.fetchServices(false).catch(() => null),
  ])
  if (clientsResponse) clients.value = clientsResponse.data
  if (servicesResponse) services.value = servicesResponse.services
})

watch([serviceId, selectedDate], async ([svcId, date]) => {
  if (!svcId || !date) {
    slots.value = []
    if (startTime.value && !props.appointment) setFieldValue('startTime', '')
    return
  }
  isFetchingSlots.value = true
  slotsError.value = null
  try {
    const response = await appointmentApi.getAvailableSlots(date, svcId)
    let fetchedSlots = response.slots

    // In edit mode, the current appointment's slot is "taken" by itself and won't
    // appear in available slots. Prepend it so the user can keep the same time.
    if (props.appointment) {
      const originalSlot = { startTime: props.appointment.startTime, endTime: props.appointment.endTime }
      const alreadyPresent = fetchedSlots.some((s) => s.startTime === originalSlot.startTime)
      if (!alreadyPresent) {
        fetchedSlots = [originalSlot, ...fetchedSlots]
      }
    }

    slots.value = fetchedSlots

    // In create mode: reset selection if the previously selected slot is gone
    if (!props.appointment && startTime.value && !fetchedSlots.some((s) => s.startTime === startTime.value)) {
      setFieldValue('startTime', '')
    }
  } catch {
    slotsError.value = t('appointments.error.slotsLoadFailed')
    slots.value = []
  } finally {
    isFetchingSlots.value = false
  }
})

const onSubmit = handleSubmit((values) => {
  generalError.value = null
  emit('submit', {
    clientId: values.clientId,
    serviceId: values.serviceId,
    startTime: values.startTime,
    notes: values.notes || null,
  })
})

function setError(field: string, message: string) {
  if (field === '_general') {
    generalError.value = message
  } else {
    setFieldError(field, message)
  }
}

defineExpose({ setError })
</script>

<template>
  <form novalidate @submit="onSubmit">
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-5">
      <AppointmentClientSearchSelect
        :model-value="clientId"
        :clients="clients"
        :error="clientIdError"
        @update:model-value="setFieldValue('clientId', $event ?? '')"
      />

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
          {{ t('appointments.form.service') }}
          <span class="text-error" aria-hidden="true">*</span>
        </label>
        <select
          v-model="serviceId"
          class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 hover:border-gray-400"
          :class="serviceIdError
            ? 'border-error focus:border-error focus:ring-error/15'
            : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15'"
        >
          <option value="" disabled>{{ t('appointments.form.servicePlaceholder') }}</option>
          <option v-for="svc in services" :key="svc.id" :value="svc.id">
            {{ svc.name }} ({{ svc.durationMinutes }}min)
          </option>
        </select>
        <p v-if="serviceIdError" class="mt-1 text-sm text-error" role="alert">{{ serviceIdError }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
          {{ t('appointments.form.date') }}
          <span class="text-error" aria-hidden="true">*</span>
        </label>
        <input
          v-model="selectedDate"
          type="date"
          class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:border-primary-700 focus:ring-primary-700/15 hover:border-gray-400"
        />
      </div>

      <AppointmentSlotPicker
        v-if="selectedDate && serviceId"
        :model-value="startTime"
        :slots="slots"
        :is-loading="isFetchingSlots"
        :error="startTimeError ?? slotsError ?? undefined"
        @update:model-value="setFieldValue('startTime', $event ?? '')"
      />

      <UiTextarea
        v-model="notes"
        :label="t('appointments.form.notes')"
        :placeholder="t('appointments.form.notesPlaceholder')"
        :rows="3"
        :error="notesError"
      />
    </div>

    <div class="mt-6 flex gap-3">
      <UiButton variant="secondary" @click="emit('cancel')">
        {{ t('common.cancel') }}
      </UiButton>
      <UiButton type="submit" :loading="loading">
        {{ t('common.save') }}
      </UiButton>
    </div>
  </form>
</template>
