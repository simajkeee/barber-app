<script setup lang="ts">
import type { SubscriptionResponse } from '~/types/subscription'
import { FetchError } from 'ofetch'

definePageMeta({
  layout: 'dashboard',
  middleware: ['auth'],
})

const { t } = useI18n()
const { getSubscription } = useSubscriptionApi()

const subscription = ref<SubscriptionResponse | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

async function fetchSubscription() {
  isLoading.value = true
  error.value = null
  try {
    subscription.value = await getSubscription()
  } catch (err) {
    if (err instanceof FetchError && err.data?.code === 'SUBSCRIPTION_NOT_FOUND') {
      error.value = t('subscription.error.setupInProgress')
    } else {
      error.value = t('subscription.error.loadFailed')
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchSubscription)
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('subscription.title')" />

    <!-- Loading -->
    <div v-if="isLoading" class="mt-6 flex justify-center">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="mt-6 space-y-3">
      <UiAlert type="error" :message="error" />
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
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          :plan="subscription.plan"
          :status="subscription.status"
          :start-date="subscription.startDate"
          :end-date="subscription.endDate"
          :days-remaining="subscription.daysRemaining"
        />
        <SubscriptionUsageCard
          :appointments-this-month="subscription.usage.appointmentsThisMonth"
          :appointment-limit="subscription.usage.appointmentLimit"
          :limit-reached="subscription.usage.limitReached"
        />
      </div>

      <SubscriptionUpgradePrompt :plan="subscription.plan" />
    </div>
  </div>
</template>
