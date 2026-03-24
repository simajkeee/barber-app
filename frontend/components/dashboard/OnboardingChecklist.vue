<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const onboardingStore = useOnboardingStore()

interface Step {
  key: string
  label: string
  done: boolean
  link: string
}

const steps = computed<Step[]>(() => [
  {
    key: 'shop',
    label: t('onboarding.steps.shop'),
    done: onboardingStore.shopCreated,
    link: localePath('/dashboard/shop/create'),
  },
  {
    key: 'service',
    label: t('onboarding.steps.service'),
    done: onboardingStore.serviceAdded,
    link: localePath('/dashboard/shop/services'),
  },
  {
    key: 'schedule',
    label: t('onboarding.steps.schedule'),
    done: onboardingStore.scheduleConfigured,
    link: localePath('/dashboard/shop/schedule'),
  },
  {
    key: 'client',
    label: t('onboarding.steps.client'),
    done: onboardingStore.clientAdded,
    link: localePath('/dashboard/clients/create'),
  },
])

</script>

<template>
  <div v-if="onboardingStore.isOnboarding" class="rounded-xl border border-gray-200 bg-white p-6">
    <div>
      <h2 class="text-base font-semibold text-gray-900">
        {{ t('onboarding.title') }}
      </h2>
      <p class="mt-0.5 text-sm text-gray-500">
        {{ t('onboarding.subtitle') }}
      </p>
    </div>

    <ul role="list" class="mt-5 space-y-3">
      <li v-for="step in steps" :key="step.key">
        <NuxtLink
          :to="step.link"
          class="flex items-center gap-3 text-sm"
          :class="step.done
            ? 'text-gray-400'
            : 'font-medium text-gray-800 hover:text-primary-700'"
          :aria-label="step.done
            ? `${step.label} — ${t('onboarding.completed')}`
            : `${step.label} — ${t('onboarding.notCompleted')}`"
        >
          <!-- Completed: filled checkmark -->
          <svg
            v-if="step.done"
            class="h-5 w-5 shrink-0 text-green-500"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
              clip-rule="evenodd"
            />
          </svg>
          <!-- Incomplete: circle outline -->
          <svg
            v-else
            class="h-5 w-5 shrink-0 text-gray-300"
            viewBox="0 0 20 20"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            aria-hidden="true"
          >
            <circle cx="10" cy="10" r="8.25" />
          </svg>

          <span :class="step.done ? 'line-through' : ''">{{ step.label }}</span>

          <svg
            class="ml-auto h-4 w-4 shrink-0 text-gray-400"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
              clip-rule="evenodd"
            />
          </svg>
        </NuxtLink>
      </li>
    </ul>

    <div class="mt-5 flex justify-end">
      <button
        type="button"
        class="text-sm text-gray-400 hover:text-gray-600"
        :aria-label="t('onboarding.dismissLabel')"
        @click="onboardingStore.dismiss()"
      >
        {{ t('onboarding.dismiss') }}
      </button>
    </div>
  </div>
</template>
