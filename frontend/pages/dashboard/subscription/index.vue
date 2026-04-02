<script setup lang="ts">
import type { SubscriptionResponse } from '~/types/subscription'

definePageMeta({
  layout: 'dashboard',
  middleware: ['auth'],
})

const { t, locale } = useI18n()
const route = useRoute()
const toast = useToast()
const { getSubscription } = useSubscriptionApi()

const subscription = ref<SubscriptionResponse | null>(null)
const errorMessage = ref<string | null>(null)
const processingPayment = ref(false)

async function fetchSubscription() {
  errorMessage.value = null
  try {
    subscription.value = await getSubscription()
  } catch (err) {
    if ((err as { data?: { code?: string } })?.data?.code === 'SUBSCRIPTION_NOT_FOUND') {
      errorMessage.value = t('subscription.error.setupInProgress')
    } else {
      errorMessage.value = t('subscription.error.loadFailed')
    }
  }
}

await useAsyncData('subscription', fetchSubscription)

onMounted(async () => {
  if (route.query.payment === 'success') {
    await fetchSubscription()
    if (subscription.value?.plan === 'pro') {
      toast.success('subscription.upgrade.success')
    } else {
      processingPayment.value = true
      setTimeout(async () => {
        await fetchSubscription()
        processingPayment.value = false
      }, 3000)
    }
    window.history.replaceState(window.history.state, '', route.path)
  }
})

const formattedEndDate = computed(() => {
  if (!subscription.value?.endDate) return ''
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'Asia/Ho_Chi_Minh',
  }).format(new Date(subscription.value.endDate))
})
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('subscription.title')" />

    <!-- Error -->
    <div v-if="errorMessage" class="mt-6 space-y-3">
      <UiAlert type="error" :message="errorMessage" />
      <div class="flex gap-3">
        <button
          type="button"
          class="text-sm text-primary-600 hover:underline"
          @click="fetchSubscription"
        >
          {{ t('subscription.error.retry') }}
        </button>
        <span class="text-gray-400">·</span>
        <a href="mailto:support@barberpro.vn" class="text-sm text-gray-500 hover:underline">
          {{ t('subscription.error.contactSupport') }}
        </a>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="subscription" class="mt-6 space-y-6">
      <UiAlert
        v-if="processingPayment"
        type="info"
        :message="t('subscription.upgrade.processing')"
      />
      <UiAlert
        v-else-if="subscription.isExpiringSoon"
        type="warning"
        :message="t('subscription.upgrade.expiringSoon', { date: formattedEndDate })"
      />

      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          :plan="subscription.plan"
          :status="subscription.status"
          :start-date="subscription.startDate"
          :end-date="subscription.endDate"
          :days-remaining="subscription.daysRemaining ?? undefined"
        />
        <SubscriptionUsageCard
          :appointments-this-month="subscription.usage.appointmentsThisMonth"
          :appointment-limit="subscription.usage.appointmentLimit"
          :limit-reached="subscription.usage.limitReached"
        />
      </div>

      <SubscriptionUpgradePrompt
        :plan="subscription.plan"
        :status="subscription.status"
        :is-expiring-soon="subscription.isExpiringSoon"
      />
    </div>
  </div>
</template>