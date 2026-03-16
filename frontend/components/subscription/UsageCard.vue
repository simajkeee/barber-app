<script setup lang="ts">
const props = defineProps<{
  appointmentsThisMonth: number
  appointmentLimit: number | null
  limitReached: boolean
}>()

const { t } = useI18n()

const usagePercent = computed(() => {
  if (props.appointmentLimit === null) return 0
  return Math.min(100, Math.round((props.appointmentsThisMonth / props.appointmentLimit) * 100))
})

const progressBarClass = computed(() => {
  if (props.limitReached) return 'bg-red-500'
  if (usagePercent.value >= 80) return 'bg-yellow-500'
  return 'bg-primary-500'
})
</script>

<template>
  <div class="rounded-xl border bg-white p-6 shadow-card">
    <h3 class="text-lg font-semibold text-gray-900">
      {{ t('subscription.usage.title') }}
    </h3>

    <div class="mt-4">
      <div class="flex items-baseline justify-between">
        <span class="text-3xl font-bold text-gray-900">
          {{ appointmentsThisMonth }}
        </span>
        <span class="text-sm text-gray-500">
          <template v-if="appointmentLimit !== null">
            / {{ appointmentLimit }} {{ t('subscription.usage.count') }}
          </template>
          <template v-else>
            {{ t('subscription.usage.unlimited') }}
          </template>
        </span>
      </div>

      <!-- Progress bar for FREE plan -->
      <div
        v-if="appointmentLimit !== null"
        class="mt-3"
      >
        <div
          class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200"
          role="progressbar"
          :aria-valuenow="appointmentsThisMonth"
          :aria-valuemin="0"
          :aria-valuemax="appointmentLimit"
        >
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="progressBarClass"
            :style="{ width: `${usagePercent}%` }"
          />
        </div>
        <p class="mt-1 text-xs text-gray-500">
          {{ t('subscription.usage.remaining', { count: Math.max(0, appointmentLimit - appointmentsThisMonth) }) }}
        </p>
      </div>

      <!-- Limit reached warning -->
      <UiAlert
        v-if="limitReached"
        type="warning"
        :message="t('subscription.usage.limitReached')"
        class="mt-4"
      />
    </div>
  </div>
</template>
