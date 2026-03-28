<script setup lang="ts">
import type { BookingResponse, PublicService, PublicShopInfo } from '~/types/booking'
import { FetchError } from 'ofetch'

definePageMeta({
  layout: 'booking',
})

const route = useRoute()
const { t } = useI18n()
const api = usePublicBookingApi()

const slug = computed(() => route.params.slug as string)

const { data: shop, pending: loading, error: shopError, refresh: refreshShop } = useAsyncData<PublicShopInfo>(
  `shop-${slug.value}`,
  async () => {
    try {
      return await api.getShopInfo(slug.value)
    } catch (err) {
      if (err instanceof FetchError && err.response?.status === 404) {
        throw createError({ statusCode: 404, fatal: false })
      }
      throw createError({ statusCode: 503, fatal: false })
    }
  },
  { default: () => null },
)

const notFound = computed(() => shopError.value?.statusCode === 404)
const shopUnavailable = computed(() => !!shopError.value && !notFound.value)

useSeoMeta({
  title: () => shop.value ? `${shop.value.name} — ${t('booking.title')}` : t('booking.title'),
  description: () => shop.value ? `${t('booking.subtitle')} ${shop.value.name}. ${shop.value.address}` : '',
})
useHead({
  script: () => shop.value ? [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'LocalBusiness',
      name: shop.value.name,
      address: { '@type': 'PostalAddress', streetAddress: shop.value.address },
      telephone: shop.value.phone,
    }),
  }] : [],
})

const step = ref<'service' | 'datetime' | 'details' | 'confirm' | 'success'>('service')
const selectedService = ref<PublicService | null>(null)
const selectedDate = ref<string | null>(null)
const selectedTime = ref<string | null>(null)
const clientName = ref('')
const clientPhone = ref('')
const captchaToken = ref('')
const submitting = ref(false)
const bookingResult = ref<BookingResponse | null>(null)
const bookingError = ref('')

const currentStepNumber = computed(() => {
  const map = { service: 1, datetime: 2, details: 3, confirm: 4, success: 5 }
  return map[step.value]
})

const stepLabels = computed(() => [
  t('booking.steps.service'),
  t('booking.steps.datetime'),
  t('booking.steps.details'),
  t('booking.steps.confirm'),
])

// Step handlers
function onSelectService(service: PublicService) {
  selectedService.value = service
  step.value = 'datetime'
}

function onSelectDate(date: string) {
  selectedDate.value = date
  selectedTime.value = null
}

function onSelectTime(time: string) {
  selectedTime.value = time
  step.value = 'details'
}

const detailsStepRef = ref<{ resetCaptcha: () => void } | null>(null)

function onDetailsSubmit(data: { clientName: string; clientPhone: string; captchaToken: string }) {
  clientName.value = data.clientName
  clientPhone.value = data.clientPhone
  captchaToken.value = data.captchaToken
  step.value = 'confirm'
}

async function onConfirm() {
  if (!selectedService.value || !selectedDate.value || !selectedTime.value) return

  submitting.value = true
  bookingError.value = ''

  try {
    bookingResult.value = await api.createBooking(slug.value, {
      clientName: clientName.value,
      clientPhone: clientPhone.value,
      serviceId: selectedService.value.id,
      date: selectedDate.value,
      time: selectedTime.value,
      captchaToken: captchaToken.value,
    })
    step.value = 'success'
  } catch (err) {
    if (err instanceof FetchError && err.data) {
      const code = err.data.code
      if (code === 'CAPTCHA_INVALID') {
        bookingError.value = t('booking.error.captchaInvalid')
        captchaToken.value = ''
        detailsStepRef.value?.resetCaptcha()
      } else if (code === 'SLOT_UNAVAILABLE') {
        bookingError.value = t('booking.error.slotUnavailable')
        step.value = 'datetime'
        selectedTime.value = null
      } else if (code === 'BOOKING_RATE_LIMIT_EXCEEDED') {
        bookingError.value = t('booking.error.rateLimited')
      } else if (code === 'APPOINTMENT_LIMIT_REACHED') {
        bookingError.value = t('booking.error.limitReached')
      } else {
        bookingError.value = err.data.message || t('booking.error.generic')
      }
    } else {
      bookingError.value = t('booking.error.generic')
    }
  } finally {
    submitting.value = false
  }
}

function onBack() {
  if (step.value === 'confirm') step.value = 'details'
  else if (step.value === 'details') step.value = 'datetime'
  else if (step.value === 'datetime') step.value = 'service'
}

function onBookAnother() {
  step.value = 'service'
  selectedService.value = null
  selectedDate.value = null
  selectedTime.value = null
  clientName.value = ''
  clientPhone.value = ''
  captchaToken.value = ''
  bookingResult.value = null
  bookingError.value = ''
}

</script>

<template>
  <!-- Loading -->
  <div v-if="loading" class="text-center py-16">
    <div class="animate-spin h-8 w-8 border-3 border-primary-500 border-t-transparent rounded-full mx-auto" />
  </div>

  <!-- Not found -->
  <div v-else-if="notFound" class="text-center py-16 px-4">
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ t('booking.error.shopNotFound') }}</h1>
    <NuxtLink to="/" class="text-sm text-primary-600 hover:underline">
      {{ t('booking.error.backToHome') }}
    </NuxtLink>
  </div>

  <!-- Unavailable -->
  <div v-else-if="shopUnavailable" class="text-center py-16 px-4">
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ t('booking.error.shopUnavailable') }}</h1>
    <p class="text-gray-500 mb-6">{{ t('booking.error.shopUnavailableDesc') }}</p>
    <div class="flex items-center justify-center gap-4">
      <button type="button" class="text-sm text-primary-600 hover:underline" @click="() => refreshShop()">
        {{ t('common.retry') }}
      </button>
      <span class="text-gray-300">|</span>
      <NuxtLink to="/" class="text-sm text-gray-500 hover:underline">
        {{ t('booking.error.backToHome') }}
      </NuxtLink>
    </div>
  </div>

  <!-- Main content -->
  <div v-else-if="shop">
    <BookingShopHeader :shop="shop" />

    <!-- Error alert -->
    <div v-if="bookingError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
      {{ bookingError }}
      <button type="button" class="ml-2 underline" @click="bookingError = ''">×</button>
    </div>

    <!-- Steps (hide on success) -->
    <BookingStepIndicator
      v-if="step !== 'success'"
      :current-step="currentStepNumber"
      :total-steps="4"
      :labels="stepLabels"
    />

    <div class="bg-white rounded-lg shadow-card p-6">
      <!-- Step 1: Service -->
      <BookingServiceStep
        v-if="step === 'service'"
        :services="shop.services"
        :selected-id="selectedService?.id ?? null"
        @select="onSelectService"
      />

      <!-- Step 2: DateTime -->
      <div v-else-if="step === 'datetime'">
        <BookingDateTimeStep
          :shop="shop"
          :service-id="selectedService!.id"
          :slug="slug"
          :selected-date="selectedDate"
          :selected-time="selectedTime"
          @select-date="onSelectDate"
          @select-time="onSelectTime"
        />
        <button
          type="button"
          class="mt-4 text-sm text-primary-600 hover:underline"
          @click="onBack"
        >
          ← {{ t('common.back') }}
        </button>
      </div>

      <!-- Step 3: Details -->
      <div v-else-if="step === 'details'">
        <BookingDetailsStep
          ref="detailsStepRef"
          :initial-name="clientName"
          :initial-phone="clientPhone"
          @submit="onDetailsSubmit"
        />
        <button
          type="button"
          class="mt-4 text-sm text-primary-600 hover:underline"
          @click="onBack"
        >
          ← {{ t('common.back') }}
        </button>
      </div>

      <!-- Step 4: Confirm -->
      <BookingConfirmStep
        v-else-if="step === 'confirm'"
        :service="selectedService!"
        :date="selectedDate!"
        :time="selectedTime!"
        :client-name="clientName"
        :client-phone="clientPhone"
        :submitting="submitting"
        @confirm="onConfirm"
        @back="onBack"
      />

      <!-- Success -->
      <BookingSuccessView
        v-else-if="step === 'success' && bookingResult"
        :booking="bookingResult"
        :service="selectedService!"
        @book-another="onBookAnother"
      />
    </div>
  </div>
</template>
