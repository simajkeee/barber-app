<script setup lang="ts">
import type { SubscriptionPlan } from '~/types/subscription'

defineProps<{
  plan: SubscriptionPlan
}>()

const { t } = useI18n()
</script>

<template>
  <div
    v-if="plan === 'free'"
    class="rounded-xl border border-primary-200 bg-primary-50 p-6"
  >
    <div class="flex items-start gap-4">
      <div class="flex-shrink-0 rounded-lg bg-primary-100 p-3">
        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-primary-900">
          {{ t('subscription.upgrade.title') }}
        </h3>
        <p class="mt-1 text-sm text-primary-700">
          {{ t('subscription.upgrade.description') }}
        </p>
        <p class="mt-3 text-sm font-medium text-primary-800">
          {{ t('subscription.upgrade.contact') }}
        </p>
      </div>
    </div>
  </div>
</template>
