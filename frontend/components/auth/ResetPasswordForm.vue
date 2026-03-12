<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { resetPasswordSchema, type ResetPasswordFormValues } from '~/schemas/auth'

const props = defineProps<{
  token: string
}>()

const { t } = useI18n()
const { resetPassword } = useAuth()
const localePath = useLocalePath()

const { handleSubmit, setFieldError, isSubmitting } = useForm<ResetPasswordFormValues>({
  validationSchema: toTypedSchema(resetPasswordSchema),
  initialValues: { password: '' },
})

const { value: password, errorMessage: passwordError } = useField<string>('password')

const generalError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  generalError.value = null

  const result = await resetPassword(props.token, values.password)

  if (result.success) {
    await navigateTo({ path: localePath('/login'), query: { resetSuccess: 'true' } })
    return
  }

  if (result.fieldErrors) {
    for (const [field, message] of Object.entries(result.fieldErrors)) {
      setFieldError(field as keyof ResetPasswordFormValues, message)
    }
  } else if (result.error === 'INVALID_RESET_TOKEN') {
    generalError.value = t('auth.error.invalidResetToken')
  } else {
    generalError.value = result.error ?? null
  }
})
</script>

<template>
  <form @submit="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <UiInput
      v-model="password"
      type="password"
      :label="t('auth.resetPassword.password')"
      autocomplete="new-password"
      required
      :error="passwordError"
    />

    <UiButton
      type="submit"
      full-width
      :loading="isSubmitting"
      class="mt-6"
    >
      {{ t('auth.resetPassword.submit') }}
    </UiButton>
  </form>
</template>