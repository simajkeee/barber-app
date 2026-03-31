<script setup lang="ts">
import type { SubscriptionPlan, SubscriptionStatus } from '~/types/subscription'

const props = defineProps<{
  plan: SubscriptionPlan
  status: SubscriptionStatus
  isExpiringSoon: boolean
}>()

const { t } = useI18n()
const { checkout } = useSubscriptionApi()
const toast = useToast()

const isLoading = ref(false)

const features = computed(() => [
  t('subscription.upgrade.feature1'),
  t('subscription.upgrade.feature2'),
  t('subscription.upgrade.feature3'),
])

const showUpgrade = computed(() => props.plan === 'free')
const showRenew = computed(() => props.plan === 'pro' && (props.isExpiringSoon || props.status === 'expired'))

async function startCheckout() {
  isLoading.value = true
  try {
    const { payUrl } = await checkout()
    window.location.href = payUrl
  } catch {
    toast.error('subscription.error.checkoutFailed')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div
    v-if="showUpgrade || showRenew"
    class="rounded-xl border border-primary-200 bg-primary-50 p-6"
  >
    <!-- Header -->
    <div class="flex items-start gap-4">
      <div class="flex-shrink-0 rounded-lg bg-primary-100 p-3">
        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
        </svg>
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-primary-900">
          {{ t('subscription.upgrade.title') }}
        </h3>
        <p class="mt-1 text-sm text-primary-700">
          {{ t('subscription.upgrade.description') }}
        </p>
      </div>
    </div>

    <!-- Feature list -->
    <ul class="mt-4 space-y-2">
      <li
        v-for="feature in features"
        :key="feature"
        class="flex items-center gap-2 text-sm text-primary-800"
      >
        <svg class="h-4 w-4 flex-shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        {{ feature }}
      </li>
    </ul>

    <!-- CTA -->
    <div class="mt-5">
      <button
        type="button"
        :disabled="isLoading"
        class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
        @click="startCheckout"
      >
        <span v-if="isLoading">{{ t('common.loading') }}</span>
        <span v-else-if="showRenew">{{ t('subscription.upgrade.renewCta') }}</span>
        <span v-else>{{ t('subscription.upgrade.cta') }}</span>
      </button>
    </div>
  </div>
</template>