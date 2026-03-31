import { vi } from 'vitest'
import { ref, reactive, computed, toRef, toRefs, watch, watchEffect, nextTick, onMounted, onUnmounted } from 'vue'
import { config } from '@vue/test-utils'
import { defineStore, storeToRefs } from 'pinia'
import { FetchError } from 'ofetch'
import { z } from 'zod'

// Configure Zod to return i18n-style keys (mirrors plugins/zod-i18n.ts)
z.setErrorMap((issue) => {
  if (issue.code === 'too_small' && issue.type === 'string') {
    if (issue.minimum === 1) return { message: 'validation.required' }
    return { message: 'validation.minLength' }
  }
  // Zod v3 emits invalid_string (not invalid_format) for email/regex validations
  if (issue.code === 'invalid_string' && issue.validation === 'email') {
    return { message: 'validation.emailInvalid' }
  }
  if (issue.code === 'invalid_string' && issue.validation === 'regex') {
    return { message: 'validation.phoneInvalid' }
  }
  return { message: 'validation.invalid' }
})

// Vue auto-imports
vi.stubGlobal('ref', ref)
vi.stubGlobal('reactive', reactive)
vi.stubGlobal('computed', computed)
vi.stubGlobal('toRef', toRef)
vi.stubGlobal('toRefs', toRefs)
vi.stubGlobal('watch', watch)
vi.stubGlobal('watchEffect', watchEffect)
vi.stubGlobal('nextTick', nextTick)
vi.stubGlobal('onMounted', onMounted)
vi.stubGlobal('onUnmounted', onUnmounted)

// Pinia auto-imports
vi.stubGlobal('defineStore', defineStore)
vi.stubGlobal('storeToRefs', storeToRefs)

// Pinia store auto-imports — must be available globally since Nuxt auto-imports them
// Individual tests that need to mock store behavior should import and override
vi.stubGlobal('useAuthStore', vi.fn())
vi.stubGlobal('useShopStore', vi.fn(() => ({
  clearShop: vi.fn(),
  shop: null,
  hasShop: false,
  schedule: [],
  isLoading: false,
  setShop: vi.fn(),
  fetchShop: vi.fn(),
})))
vi.stubGlobal('useOnboardingStore', vi.fn())

// Composable auto-imports
vi.stubGlobal('useApiError', () => ({
  parseApiError: (err: unknown) => {
    if (!(err instanceof FetchError) || !err.data) {
      return { error: 'unexpected' }
    }
    const data = err.data as Record<string, unknown>
    const details = Array.isArray(data.details) ? data.details as { field: string; message: string }[] : []
    const fieldErrors = details.length
      ? Object.fromEntries(details.map((d) => [d.field, d.message]))
      : undefined
    return { error: (data.code ?? data.message) as string, fieldErrors }
  },
}))
vi.stubGlobal('useShopApi', vi.fn())
vi.stubGlobal('useClientApi', vi.fn())
vi.stubGlobal('useAppointmentApi', vi.fn())
vi.stubGlobal('useReminderApi', vi.fn())
vi.stubGlobal('usePublicBookingApi', vi.fn())
vi.stubGlobal('useSubscriptionApi', vi.fn())
vi.stubGlobal('useToast', () => ({ success: vi.fn(), error: vi.fn() }))
vi.stubGlobal('useFormatters', () => ({
  formatPrice: (price: number) => `${price} ₫`,
  formatDuration: (min: number) => `${min} min`,
  formatDate: (iso: string) => new Date(iso).toLocaleDateString(),
}))

// Mock Nuxt auto-imports globally
vi.stubGlobal('navigateTo', vi.fn())
vi.stubGlobal('useRuntimeConfig', () => ({
  public: {
    apiBase: '/api/v1',
    siteUrl: 'http://localhost:3000',
    facebookAppId: '',
    turnstileSiteKey: '1x00000000000000000000AA',
  },
}))
vi.stubGlobal('useCookie', (_name: string) => ref(null))
vi.stubGlobal('useState', (_key: string, init?: () => unknown) => ref(init?.() ?? null))
vi.stubGlobal('useAsyncData', (_key: string, fetcher: () => Promise<unknown>, options?: { default?: () => unknown }) => {
  const data = ref<unknown>(options?.default?.() ?? null)
  const pending = ref(true)
  const error = ref<{ statusCode?: number } | null>(null)
  const refresh = async () => {
    pending.value = true
    try {
      data.value = await fetcher()
      error.value = null
    } catch (err) {
      error.value = err as { statusCode?: number }
      data.value = options?.default?.() ?? null
    } finally {
      pending.value = false
    }
  }
  const result = { data, pending, error, refresh }
  // Return a thenable so `await useAsyncData(...)` resolves after fetch completes,
  // while also exposing properties directly for non-await usage.
  return Object.assign(refresh().then(() => result), result)
})
vi.stubGlobal('createError', (opts: { statusCode?: number; fatal?: boolean }) => {
  const err = new Error(String(opts.statusCode))
  ;(err as Error & { statusCode: number }).statusCode = opts.statusCode ?? 500
  return err
})
vi.stubGlobal('useId', () => 'test-id')
vi.stubGlobal('useHead', vi.fn())
vi.stubGlobal('useSeoMeta', vi.fn())
vi.stubGlobal('definePageMeta', vi.fn())
vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))
vi.stubGlobal('useSwitchLocalePath', () => (code: string) => `/${code}`)
vi.stubGlobal('useLocalePath', () => (path: string) => path)
vi.stubGlobal('useRoute', () => ({ params: {}, query: {} }))
vi.stubGlobal('useRouter', () => ({ replace: vi.fn(), push: vi.fn() }))
vi.stubGlobal('defineNuxtRouteMiddleware', (fn: Function) => fn)
vi.stubGlobal('$fetch', vi.fn())

// Stub Nuxt components globally
config.global.stubs = {
  NuxtLink: {
    template: '<a :href="to"><slot /></a>',
    props: ['to'],
  },
}

// Provide $t globally so components using it directly in templates work
config.global.mocks = {
  $t: (key: string, ...args: unknown[]) => key,
}