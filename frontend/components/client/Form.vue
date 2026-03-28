<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { clientSchema } from '~/schemas/client'
import type { Client, CreateClientRequest } from '~/types/client'

const props = withDefaults(
  defineProps<{
    client?: Client
    loading?: boolean
  }>(),
  { client: undefined, loading: false },
)

const emit = defineEmits<{
  submit: [data: CreateClientRequest]
  cancel: []
}>()

const { t } = useI18n()

const { handleSubmit, setFieldError, isSubmitting } = useForm({
  validationSchema: toTypedSchema(clientSchema),
  initialValues: {
    firstName: props.client?.firstName ?? '',
    lastName: props.client?.lastName ?? '',
    phone: props.client?.phone ?? '',
    email: props.client?.email ?? '',
    notes: props.client?.notes ?? '',
  },
})

const { value: firstName, errorMessage: firstNameError } = useField<string>('firstName')
const { value: lastName, errorMessage: lastNameError } = useField<string>('lastName')
const { value: phone, errorMessage: phoneError } = useField<string>('phone')
const { value: email, errorMessage: emailError } = useField<string>('email')
const { value: notes, errorMessage: notesError } = useField<string>('notes')

const generalError = ref<string | null>(null)

const onSubmit = handleSubmit((values) => {
  generalError.value = null
  emit('submit', {
    firstName: values.firstName,
    lastName: values.lastName,
    phone: values.phone,
    email: values.email || null,
    notes: values.notes || null,
  })
})

function setError(field: string, message: string) {
  if (field === '_general') {
    generalError.value = message
  } else {
    setFieldError(field as Parameters<typeof setFieldError>[0], message)
  }
}

defineExpose({ setError })
</script>

<template>
  <form @submit="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-4">
      <div class="grid gap-4 sm:grid-cols-2">
        <UiInput
          v-model="firstName"
          :label="t('clients.form.firstName')"
          required
          :error="firstNameError"
          autocomplete="given-name"
        />
        <UiInput
          v-model="lastName"
          :label="t('clients.form.lastName')"
          required
          :error="lastNameError"
          autocomplete="family-name"
        />
      </div>

      <UiInput
        v-model="phone"
        :label="t('clients.form.phone')"
        type="tel"
        required
        :placeholder="t('clients.form.phonePlaceholder')"
        :error="phoneError"
        autocomplete="tel"
      />

      <UiInput
        v-model="email"
        :label="t('clients.form.email')"
        type="email"
        :placeholder="t('clients.form.emailPlaceholder')"
        :error="emailError"
        autocomplete="email"
      />

      <UiTextarea
        v-model="notes"
        :label="t('clients.form.notes')"
        :placeholder="t('clients.form.notesPlaceholder')"
        :rows="3"
        :error="notesError"
      />
    </div>

    <div class="mt-6 flex gap-3">
      <UiButton variant="secondary" @click="emit('cancel')">
        {{ t('common.cancel') }}
      </UiButton>
      <UiButton type="submit" :loading="loading || isSubmitting">
        {{ t('common.save') }}
      </UiButton>
    </div>
  </form>
</template>