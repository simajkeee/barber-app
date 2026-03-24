<script setup lang="ts">
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { bookingDetailsSchema } from '~/schemas/booking'

const props = defineProps<{
  initialName?: string
  initialPhone?: string
}>()

const emit = defineEmits<{
  submit: [data: { clientName: string; clientPhone: string; captchaToken: string }]
}>()

const { t } = useI18n()
const config = useRuntimeConfig()

const { handleSubmit, errors, defineField } = useForm({
  validationSchema: toTypedSchema(bookingDetailsSchema),
  initialValues: {
    clientName: props.initialName ?? '',
    clientPhone: props.initialPhone ?? '',
  },
})

const [clientName, clientNameAttrs] = defineField('clientName')
const [clientPhone, clientPhoneAttrs] = defineField('clientPhone')

const captchaToken = ref('')
const captchaError = ref('')
const turnstileWidgetId = ref<string | null>(null)
const turnstileContainer = ref<HTMLElement | null>(null)

function onTurnstileSuccess(token: string) {
  captchaToken.value = token
  captchaError.value = ''
}

function resetCaptcha() {
  captchaToken.value = ''
  if (turnstileWidgetId.value && window.turnstile) {
    window.turnstile.reset(turnstileWidgetId.value)
  }
}

function renderWidget() {
  if (!turnstileContainer.value || !window.turnstile) return
  if (turnstileWidgetId.value) return

  turnstileWidgetId.value = window.turnstile.render(turnstileContainer.value, {
    sitekey: config.public.turnstileSiteKey,
    callback: onTurnstileSuccess,
    theme: 'light',
  })
}

onMounted(() => {
  if (window.turnstile) {
    renderWidget()
    return
  }

  const script = document.createElement('script')
  script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad'
  script.async = true
  ;(window as any).onTurnstileLoad = () => renderWidget()
  try {
    document.head.appendChild(script)
  } catch {
    // Silently fail in test environments where script loading is disabled
  }
})

const onSubmit = handleSubmit((values) => {
  if (!captchaToken.value) {
    captchaError.value = t('booking.captcha.required')
    return
  }
  emit('submit', { ...values, captchaToken: captchaToken.value })
})

defineExpose({ resetCaptcha })
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('booking.steps.details') }}</h2>
    <form class="space-y-4" @submit.prevent="onSubmit">
      <div>
        <label for="clientName" class="block text-sm font-medium text-gray-700 mb-1">
          {{ t('booking.details.name') }} <span class="text-red-500">*</span>
        </label>
        <input
          id="clientName"
          v-model="clientName"
          v-bind="clientNameAttrs"
          type="text"
          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="errors.clientName ? 'border-red-300' : 'border-gray-300'"
          :placeholder="t('booking.details.namePlaceholder')"
        />
        <p v-if="errors.clientName" class="mt-1 text-sm text-red-500">{{ errors.clientName }}</p>
      </div>

      <div>
        <label for="clientPhone" class="block text-sm font-medium text-gray-700 mb-1">
          {{ t('booking.details.phone') }} <span class="text-red-500">*</span>
        </label>
        <input
          id="clientPhone"
          v-model="clientPhone"
          v-bind="clientPhoneAttrs"
          type="tel"
          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="errors.clientPhone ? 'border-red-300' : 'border-gray-300'"
          :placeholder="t('booking.details.phonePlaceholder')"
        />
        <p v-if="errors.clientPhone" class="mt-1 text-sm text-red-500">{{ errors.clientPhone }}</p>
      </div>

      <!-- Turnstile CAPTCHA -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ t('booking.captcha.label') }}
        </label>
        <div ref="turnstileContainer" class="cf-turnstile" />
        <p v-if="captchaError" class="mt-1 text-sm text-red-500">{{ captchaError }}</p>
      </div>

      <button
        type="submit"
        class="w-full bg-primary-600 text-white py-2.5 rounded-lg font-medium hover:bg-primary-700 transition-colors"
      >
        {{ t('common.confirm') }}
      </button>
    </form>
  </div>
</template>
