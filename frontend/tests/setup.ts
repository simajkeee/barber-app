import { vi } from 'vitest'
import { ref, reactive, computed, toRef, toRefs, watch, watchEffect, nextTick, onMounted, onUnmounted } from 'vue'
import { config } from '@vue/test-utils'
import { defineStore, storeToRefs } from 'pinia'
import { FetchError } from 'ofetch'
import { z } from 'zod'

// Configure Zod to return i18n-style keys (mirrors plugins/zod-i18n.ts)
z.setErrorMap((issue) => {
  if (issue.code === 'too_small' && issue.origin === 'string') {
    if (issue.minimum === 1) return { message: 'validation.required' }
    return { message: 'validation.minLength' }
  }
  if (issue.code === 'invalid_format' && issue.format === 'email') {
    return { message: 'validation.emailInvalid' }
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
vi.stubGlobal('useShopStore', vi.fn())

// Composable auto-imports
vi.stubGlobal('useApiError', () => ({
  parseApiError: (err: unknown) => {
    if (!(err instanceof FetchError) || !(err as any).data) {
      return { error: 'unexpected' }
    }
    const data = (err as any).data
    const fieldErrors = data.details?.length
      ? Object.fromEntries(data.details.map((d: any) => [d.field, d.message]))
      : undefined
    return { error: data.code ?? data.message, fieldErrors }
  },
}))
vi.stubGlobal('useShopApi', vi.fn())
vi.stubGlobal('useClientApi', vi.fn())
vi.stubGlobal('useAppointmentApi', vi.fn())
vi.stubGlobal('useReminderApi', vi.fn())
vi.stubGlobal('usePublicBookingApi', vi.fn())
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
  },
}))
vi.stubGlobal('useCookie', (_name: string) => ref(null))
vi.stubGlobal('useState', (_key: string, init?: () => any) => ref(init?.() ?? null))
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
  $t: (key: string, ...args: any[]) => key,
}