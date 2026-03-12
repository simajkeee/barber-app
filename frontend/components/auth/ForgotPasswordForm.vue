<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { forgotPasswordSchema, type ForgotPasswordFormValues } from '~/schemas/auth'

const { t } = useI18n()
const { forgotPassword } = useAuth()

const { handleSubmit, isSubmitting } = useForm<ForgotPasswordFormValues>({
  validationSchema: toTypedSchema(forgotPasswordSchema),
  initialValues: { email: '' },
})

const { value: email, errorMessage: emailError } = useField<string>('email')

const generalError = ref<string | null>(null)
const submitted = ref(false)

const onSubmit = handleSubmit(async (values) => {
  generalError.value = null

  const result = await forgotPassword(values.email)

  if (result.success) {
    submitted.value = true
    return
  }

  if (result.error === 'RATE_LIMIT_EXCEEDED') {
    generalError.value = t('auth.error.rateLimitExceeded')
  } else {
    generalError.value = result.error ?? null
  }
})
</script>

<template>
  <div>
    <p class="mb-4 text-sm text-gray-600">
      {{ t('auth.forgotPassword.description') }}
    </p>

    <UiAlert
      v-if="submitted"
      type="success"
      :message="t('auth.forgotPassword.success')"
      role="status"
    />

    <form v-else @submit="onSubmit" novalidate>
      <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

      <UiInput
        v-model="email"
        type="email"
        :label="t('auth.forgotPassword.email')"
        autocomplete="email"
        required
        :error="emailError"
      />

      <UiButton
        type="submit"
        full-width
        :loading="isSubmitting"
        class="mt-6"
      >
        {{ t('auth.forgotPassword.submit') }}
      </UiButton>
    </form>
  </div>
</template>