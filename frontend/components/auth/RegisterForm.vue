<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { registerSchema, type RegisterFormValues } from '~/schemas/auth'

const emit = defineEmits<{
  success: []
}>()

const { t, locale } = useI18n()
const { register } = useAuth()

const { handleSubmit, setFieldError, isSubmitting } = useForm<RegisterFormValues>({
  validationSchema: toTypedSchema(registerSchema),
  initialValues: { firstName: '', lastName: '', email: '', password: '' },
})

const { value: firstName, errorMessage: firstNameError } = useField<string>('firstName')
const { value: lastName, errorMessage: lastNameError } = useField<string>('lastName')
const { value: email, errorMessage: emailError } = useField<string>('email')
const { value: password, errorMessage: passwordError } = useField<string>('password')

const generalError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  generalError.value = null

  const result = await register({
    ...values,
    locale: locale.value as 'vi' | 'en',
  })

  if (result.success) {
    emit('success')
    return
  }

  if (result.fieldErrors) {
    for (const [field, message] of Object.entries(result.fieldErrors)) {
      setFieldError(field as keyof RegisterFormValues, message)
    }
  } else if (result.error === 'EMAIL_ALREADY_EXISTS') {
    generalError.value = t('auth.error.emailExists')
  } else {
    generalError.value = result.error ?? null
  }
})
</script>

<template>
  <form @submit="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <UiInput
          v-model="firstName"
          :label="t('auth.register.firstName')"
          autocomplete="given-name"
          required
          :error="firstNameError"
        />

        <UiInput
          v-model="lastName"
          :label="t('auth.register.lastName')"
          autocomplete="family-name"
          required
          :error="lastNameError"
        />
      </div>

      <UiInput
        v-model="email"
        type="email"
        :label="t('auth.register.email')"
        autocomplete="email"
        required
        :error="emailError"
      />

      <UiInput
        v-model="password"
        type="password"
        :label="t('auth.register.password')"
        autocomplete="new-password"
        required
        :error="passwordError"
      />
    </div>

    <UiButton
      type="submit"
      full-width
      :loading="isSubmitting"
      class="mt-6"
    >
      {{ t('auth.register.submit') }}
    </UiButton>
  </form>
</template>