<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const config = useRuntimeConfig()

useSeoMeta({
  title: () => t('reminders.optOut.title'),
  robots: 'noindex, nofollow',
})

const rawToken = route.query.token
const token = typeof rawToken === 'string' ? rawToken : ''

const status = ref<'loading' | 'success' | 'error'>(token ? 'loading' : 'error')

async function processOptOut() {
  if (!token) return

  try {
    await $fetch(`${config.public.apiBase}/public/reminders/opt-out`, {
      method: 'POST',
      body: { token },
    })
    status.value = 'success'
  } catch {
    status.value = 'error'
  }
}

onMounted(() => {
  if (token) {
    processOptOut()
  }
})
</script>

<template>
  <div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="w-full max-w-md text-center">
      <h1 class="text-xl font-semibold text-gray-900">{{ t('reminders.optOut.title') }}</h1>

      <div v-if="status === 'loading'" class="mt-6">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-600" role="status">
          <span class="sr-only">{{ t('reminders.optOut.loading') }}</span>
        </div>
        <p class="mt-4 text-sm text-gray-500">{{ t('reminders.optOut.loading') }}</p>
      </div>

      <div v-else-if="status === 'success'" class="mt-6">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
          <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="mt-4 text-sm text-gray-700">{{ t('reminders.optOut.success') }}</p>
        <NuxtLink :to="localePath('/')" class="mt-6 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">
          {{ t('reminders.optOut.backHome') }}
        </NuxtLink>
      </div>

      <div v-else class="mt-6">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
          <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <p class="mt-4 text-sm text-gray-700">{{ t('reminders.optOut.error') }}</p>
        <NuxtLink :to="localePath('/')" class="mt-6 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">
          {{ t('reminders.optOut.backHome') }}
        </NuxtLink>
      </div>
    </div>
  </div>
</template>
