<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { loginSchema, type LoginFormValues } from '~/schemas/auth'

const emit = defineEmits<{
  success: []
}>()

const { t } = useI18n()
const { login } = useAuth()

const { handleSubmit, setFieldError, isSubmitting } = useForm<LoginFormValues>({
  validationSchema: toTypedSchema(loginSchema),
  initialValues: { email: '', password: '' },
})

const { value: email, errorMessage: emailError } = useField<string>('email')
const { value: password, errorMessage: passwordError } = useField<string>('password')

const generalError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  generalError.value = null

  const result = await login(values)

  if (result.success) {
    emit('success')
    return
  }

  if (result.fieldErrors) {
    for (const [field, message] of Object.entries(result.fieldErrors)) {
      setFieldError(field as keyof LoginFormValues, message)
    }
  } else if (result.error === 'INVALID_CREDENTIALS') {
    generalError.value = t('auth.error.invalidCredentials')
  } else {
    generalError.value = result.error ?? null
  }
})
</script>

<template>
  <form @submit="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-4">
      <UiInput
        v-model="email"
        type="email"
        :label="t('auth.login.email')"
        autocomplete="email"
        required
        :error="emailError"
      />

      <UiInput
        v-model="password"
        type="password"
        :label="t('auth.login.password')"
        autocomplete="current-password"
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
      {{ t('auth.login.submit') }}
    </UiButton>
  </form>
</template>