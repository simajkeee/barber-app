<script setup lang="ts">
import { FetchError } from 'ofetch'
import type { SubscriptionPlan } from '~/types/subscription'

defineProps<{
  plan: SubscriptionPlan
}>()

const { t } = useI18n()
const { upgradeRequest } = useSubscriptionApi()
const authStore = useAuthStore()

const showForm = ref(false)
const submitted = ref(false)
const isSubmitting = ref(false)
const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)

const name = ref('')
const email = ref(authStore.user?.email ?? '')
const phone = ref('')
const message = ref('')

const features = computed(() => [
  t('subscription.upgrade.feature1'),
  t('subscription.upgrade.feature2'),
  t('subscription.upgrade.feature3'),
])

function openForm() {
  showForm.value = true
  errorMessage.value = null
}

function cancelForm() {
  showForm.value = false
  errorMessage.value = null
}

async function submit() {
  if (!name.value.trim() || !email.value.trim()) return

  isSubmitting.value = true
  errorMessage.value = null

  try {
    await upgradeRequest({
      name: name.value.trim(),
      email: email.value.trim(),
      phone: phone.value.trim() || undefined,
      message: message.value.trim() || undefined,
    })
    submitted.value = true
    showForm.value = false
    successMessage.value = t('subscription.upgrade.successMessage')
  } catch (err) {
    if (err instanceof FetchError && err.data?.code === 'UPGRADE_REQUEST_ALREADY_SUBMITTED') {
      submitted.value = true
      showForm.value = false
      successMessage.value = t('subscription.upgrade.alreadySubmitted')
    } else {
      errorMessage.value = t('common.retry')
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    v-if="plan === 'free'"
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

    <!-- Success state -->
    <div v-if="successMessage" class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
      {{ successMessage }}
    </div>

    <!-- CTA button -->
    <div v-else-if="!showForm" class="mt-5">
      <button
        type="button"
        class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        @click="openForm"
      >
        {{ t('subscription.upgrade.requestButton') }}
      </button>
    </div>

    <!-- Inline form -->
    <div v-if="showForm && !submitted" class="mt-5 space-y-4">
      <p class="text-sm font-medium text-primary-900">
        {{ t('subscription.upgrade.formTitle') }}
      </p>

      <!-- Name -->
      <div>
        <input
          v-model="name"
          type="text"
          :placeholder="t('subscription.upgrade.namePlaceholder')"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>

      <!-- Email -->
      <div>
        <input
          v-model="email"
          type="email"
          :placeholder="t('subscription.upgrade.emailPlaceholder')"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>

      <!-- Phone (optional) -->
      <div>
        <input
          v-model="phone"
          type="tel"
          :placeholder="t('subscription.upgrade.phonePlaceholder')"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>

      <!-- Message (optional) -->
      <div>
        <textarea
          v-model="message"
          rows="3"
          :placeholder="t('subscription.upgrade.messagePlaceholder')"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>

      <!-- Error -->
      <p v-if="errorMessage" class="text-sm text-red-600">
        {{ errorMessage }}
      </p>

      <!-- Actions -->
      <div class="flex items-center gap-4">
        <button
          type="button"
          :disabled="isSubmitting || !name.trim() || !email.trim()"
          class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
          @click="submit"
        >
          <span v-if="isSubmitting">{{ t('common.loading') }}</span>
          <span v-else>{{ t('subscription.upgrade.submitButton') }}</span>
        </button>
        <button
          type="button"
          class="text-sm text-primary-600 hover:underline"
          @click="cancelForm"
        >
          {{ t('common.cancel') }}
        </button>
      </div>
    </div>
  </div>
</template>
