<script setup lang="ts">
import type { SubscriptionPlan, SubscriptionStatus } from '~/types/subscription'

const props = defineProps<{
  plan: SubscriptionPlan
  status: SubscriptionStatus
  startDate: string
  endDate: string | null
  daysRemaining?: number
}>()

const { t, d } = useI18n()

const planLabel = computed(() => t(`subscription.plan.${props.plan}`))
const statusLabel = computed(() => t(`subscription.status.${props.status}`))

const planBadgeClass = computed(() =>
  props.plan === 'pro'
    ? 'bg-primary-100 text-primary-700'
    : 'bg-green-100 text-green-700',
)

const statusBadgeClass = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-green-100 text-green-700'
    case 'expired':
      return 'bg-yellow-100 text-yellow-700'
    case 'cancelled':
      return 'bg-red-100 text-red-700'
  }
})

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString()
}
</script>

<template>
  <div class="rounded-xl border bg-white p-6 shadow-card">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ t('subscription.plan.title') }}
      </h3>
      <span
        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
        :class="planBadgeClass"
      >
        {{ planLabel }}
      </span>
    </div>

    <div class="mt-4 space-y-3">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500">{{ t('subscription.status.label') }}</span>
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="statusBadgeClass"
        >
          {{ statusLabel }}
        </span>
      </div>

      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500">{{ t('subscription.startDate') }}</span>
        <span class="text-gray-900">{{ formatDate(startDate) }}</span>
      </div>

      <div v-if="endDate" class="flex items-center justify-between text-sm">
        <span class="text-gray-500">{{ t('subscription.endDate') }}</span>
        <span class="text-gray-900">{{ formatDate(endDate) }}</span>
      </div>

      <div
        v-if="plan === 'pro' && daysRemaining !== undefined"
        class="flex items-center justify-between text-sm"
      >
        <span class="text-gray-500">{{ t('subscription.daysRemaining') }}</span>
        <span class="font-medium text-primary-600">
          {{ t('subscription.daysRemainingCount', { count: daysRemaining }) }}
        </span>
      </div>
    </div>
  </div>
</template>
