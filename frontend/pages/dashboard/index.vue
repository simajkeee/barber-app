<script setup lang="ts">
import type { Appointment } from '~/types/appointment'
import type { SubscriptionResponse } from '~/types/subscription'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t, locale } = useI18n()
const localePath = useLocalePath()
const authStore = useAuthStore()
const shopStore = useShopStore()
const onboardingStore = useOnboardingStore()
const appointmentApi = useAppointmentApi()
const subscriptionApi = useSubscriptionApi()
const shopApi = useShopApi()

const NuxtLinkComp = resolveComponent('NuxtLink')

const todayCount = ref(0)
const isTodayLoading = ref(false)

const nextAppointment = ref<Appointment | null>(null)
const isNextLoading = ref(false)

const subscription = ref<SubscriptionResponse | null>(null)
const isSubLoading = ref(false)

function todayDate() {
  return new Date().toISOString().slice(0, 10)
}

async function loadStats() {
  const today = todayDate()
  isTodayLoading.value = true
  isNextLoading.value = true
  isSubLoading.value = true

  await Promise.allSettled([
    appointmentApi
      .getDailySchedule(today)
      .then(r => { todayCount.value = r.appointments.length })
      .finally(() => { isTodayLoading.value = false }),

    appointmentApi
      .listAppointments({ dateFrom: today, status: ['scheduled'], limit: 1 })
      .then(r => { nextAppointment.value = r.data[0] ?? null })
      .finally(() => { isNextLoading.value = false }),

    subscriptionApi
      .getSubscription()
      .then(r => { subscription.value = r })
      .finally(() => { isSubLoading.value = false }),

    shopApi
      .fetchServices()
      .then(r => { onboardingStore.setServiceAdded(r.services.length > 0) })
      .catch(() => {}),
  ])
}

onMounted(async () => {
  onboardingStore.init()

  if (!shopStore.shop && !shopStore.isLoading) {
    await shopStore.fetchShop()
  }
  if (shopStore.hasShop) {
    loadStats()
    onboardingStore.fetchChecklistData()
  }
})

const usagePercent = computed(() => {
  if (!subscription.value?.usage.appointmentLimit) return 0
  const { appointmentsThisMonth, appointmentLimit } = subscription.value.usage
  return Math.min(100, Math.round((appointmentsThisMonth / appointmentLimit!) * 100))
})

const nextAppointmentTime = computed(() => {
  if (!nextAppointment.value) return null
  const start = new Date(nextAppointment.value.startTime)
  return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Asia/Ho_Chi_Minh',
    hour12: false,
  }).format(start)
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900">{{ t('dashboard.title') }}</h1>
    <p class="mt-1 text-gray-500">{{ authStore.fullName }}</p>

    <div v-if="shopStore.isLoading" class="mt-8 flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <div v-else-if="!shopStore.hasShop" class="mt-8">
      <UiEmptyState
        :title="t('dashboard.noShop')"
        :description="t('dashboard.noShopDescription')"
      >
        <template #action>
          <NuxtLink :to="localePath('/dashboard/shop/create')">
            <UiButton>{{ t('shop.create.title') }}</UiButton>
          </NuxtLink>
        </template>
      </UiEmptyState>
    </div>

    <template v-else>
      <!-- Onboarding checklist -->
      <DashboardOnboardingChecklist class="mt-6" />

      <!-- Quick actions -->
      <div class="mt-6 flex flex-wrap gap-3">
        <NuxtLink :to="localePath('/dashboard/appointments/create')">
          <UiButton>{{ t('dashboard.actions.newAppointment') }}</UiButton>
        </NuxtLink>
        <NuxtLink :to="localePath('/dashboard/clients/create')">
          <UiButton variant="secondary">{{ t('dashboard.actions.newClient') }}</UiButton>
        </NuxtLink>
      </div>

      <!-- Stats grid -->
      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- Today's appointments -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm font-medium text-gray-500">{{ t('dashboard.stats.today') }}</p>
          <div v-if="isTodayLoading" class="mt-2 h-9 w-12 animate-pulse rounded bg-gray-100" />
          <p v-else class="mt-1 text-3xl font-bold text-gray-900">{{ todayCount }}</p>
        </div>

        <!-- Next appointment -->
        <component
          :is="nextAppointment ? NuxtLinkComp : 'div'"
          :to="nextAppointment ? localePath(`/dashboard/appointments/${nextAppointment.id}`) : undefined"
          class="rounded-lg border border-gray-200 bg-white p-5 transition-colors"
          :class="nextAppointment && !isNextLoading ? 'hover:bg-gray-50' : ''"
        >
          <p class="text-sm font-medium text-gray-500">{{ t('dashboard.stats.next') }}</p>
          <div v-if="isNextLoading" class="mt-2 space-y-2">
            <div class="h-5 w-36 animate-pulse rounded bg-gray-100" />
            <div class="h-4 w-48 animate-pulse rounded bg-gray-100" />
          </div>
          <template v-else-if="nextAppointment">
            <p class="mt-1 font-semibold text-gray-900">
              {{ nextAppointment.client.firstName }} {{ nextAppointment.client.lastName }}
            </p>
            <p class="text-sm text-gray-500">
              {{ nextAppointmentTime }} · {{ nextAppointment.service.name }}
            </p>
          </template>
          <p v-else class="mt-1 text-sm text-gray-400">{{ t('dashboard.stats.nextNone') }}</p>
        </component>

        <!-- Monthly usage -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm font-medium text-gray-500">{{ t('dashboard.stats.usage') }}</p>
          <div v-if="isSubLoading" class="mt-2 space-y-2">
            <div class="h-5 w-24 animate-pulse rounded bg-gray-100" />
            <div class="h-2 w-full animate-pulse rounded bg-gray-100" />
          </div>
          <template v-else-if="subscription">
            <p class="mt-1 font-bold text-gray-900">
              <template v-if="subscription.usage.appointmentLimit">
                {{ t('dashboard.stats.appointmentsOf', {
                  used: subscription.usage.appointmentsThisMonth,
                  limit: subscription.usage.appointmentLimit,
                }) }}
              </template>
              <template v-else>
                {{ subscription.usage.appointmentsThisMonth }}
                <span class="text-base font-normal text-gray-500"> / {{ t('dashboard.stats.unlimited') }}</span>
              </template>
            </p>
            <div v-if="subscription.usage.appointmentLimit" class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
              <div
                class="h-full rounded-full transition-all"
                :class="usagePercent >= 80 ? 'bg-amber-500' : 'bg-primary-600'"
                :style="{ width: `${usagePercent}%` }"
              />
            </div>
          </template>
        </div>

        <!-- Subscription plan -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm font-medium text-gray-500">{{ t('dashboard.stats.plan') }}</p>
          <div v-if="isSubLoading" class="mt-2 space-y-2">
            <div class="h-6 w-16 animate-pulse rounded bg-gray-100" />
            <div class="h-4 w-28 animate-pulse rounded bg-gray-100" />
          </div>
          <template v-else-if="subscription">
            <p class="mt-1 text-xl font-bold capitalize text-gray-900">{{ subscription.plan }}</p>
            <p v-if="subscription.daysRemaining !== undefined" class="text-sm text-gray-500">
              {{ t('dashboard.stats.daysRemaining', { n: subscription.daysRemaining }) }}
            </p>
            <NuxtLink
              v-if="subscription.plan === 'free'"
              :to="localePath('/dashboard/subscription')"
              class="mt-2 inline-block text-sm font-medium text-primary-700 hover:underline"
            >
              {{ t('dashboard.stats.upgrade') }}
            </NuxtLink>
          </template>
        </div>
      </div>
    </template>
  </div>
</template>
