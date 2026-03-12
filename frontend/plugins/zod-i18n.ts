import { z } from 'zod'

export default defineNuxtPlugin((nuxtApp) => {
  z.setErrorMap((issue) => {
    const i18n = nuxtApp.$i18n as { t: (key: string, params?: Record<string, unknown>) => string }
    const t = i18n.t.bind(i18n)

    if (issue.code === 'too_small' && issue.origin === 'string') {
      if (issue.minimum === 1) return { message: t('validation.required') }
      return { message: t('validation.minLength', { min: issue.minimum }) }
    }

    if (issue.code === 'invalid_format' && issue.format === 'email') {
      return { message: t('validation.emailInvalid') }
    }

    return { message: t('validation.invalid') }
  })
})