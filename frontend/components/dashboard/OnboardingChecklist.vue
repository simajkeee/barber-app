<script setup lang="ts">
const props = defineProps<{
  hasShop: boolean
  hasServices: boolean
  hasSchedule: boolean
}>()

const emit = defineEmits<{
  dismiss: []
}>()

const { t } = useI18n()
const localePath = useLocalePath()

interface Step {
  key: string
  label: string
  done: boolean
  link: string
}

const steps = computed<Step[]>(() => [
  {
    key: 'step1',
    label: t('onboarding.step1'),
    done: props.hasShop,
    link: localePath('/dashboard/shop/create'),
  },
  {
    key: 'step2',
    label: t('onboarding.step2'),
    done: props.hasServices,
    link: localePath('/dashboard/shop/services'),
  },
  {
    key: 'step3',
    label: t('onboarding.step3'),
    done: props.hasSchedule,
    link: localePath('/dashboard/shop/schedule'),
  },
  {
    key: 'step4',
    label: t('onboarding.step4'),
    done: false,
    link: localePath('/dashboard/shop'),
  },
])

const doneCount = computed(() => steps.value.filter(s => s.done).length)
const allDone = computed(() => doneCount.value === steps.value.length)
</script>

<template>
  <div v-if="!allDone" class="rounded-xl border border-gray-200 bg-white p-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-base font-semibold text-gray-900">
          {{ t('onboarding.title') }}
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
          {{ t('onboarding.progress', { done: doneCount, total: steps.length }) }}
        </p>
      </div>
    </div>

    <ul class="mt-5 space-y-3">
      <li v-for="step in steps" :key="step.key" class="flex items-center gap-3">
        <!-- Checkmark icon -->
        <span class="shrink-0">
          <svg
            v-if="step.done"
            class="h-5 w-5 text-green-500"
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
          <svg
            v-else
            class="h-5 w-5 text-gray-300"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
              clip-rule="evenodd"
            />
          </svg>
        </span>

        <!-- Label and arrow link -->
        <span
          v-if="step.done"
          class="text-sm text-gray-400 line-through"
        >{{ step.label }}</span>
        <NuxtLink
          v-else
          :to="step.link"
          class="flex flex-1 items-center justify-between text-sm font-medium text-gray-800 hover:text-primary-700"
        >
          <span>{{ step.label }}</span>
          <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
        @click="emit('dismiss')"
      >
        {{ t('onboarding.dismiss') }}
      </button>
    </div>
  </div>
</template>
