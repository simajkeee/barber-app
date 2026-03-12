import { ref, computed } from 'vue'

export const navigateTo = vi.fn()
export const useRuntimeConfig = vi.fn(() => ({
  public: {
    apiBase: '/api/v1',
    siteUrl: 'http://localhost:3000',
    facebookAppId: '',
  },
}))
export const useCookie = vi.fn((name: string) => ref(null))
export const useState = vi.fn((_key: string, init?: () => any) => ref(init?.() ?? null))
export const useId = vi.fn(() => 'test-id')
export const useHead = vi.fn()
export const useSeoMeta = vi.fn()
export const useRoute = vi.fn(() => ({ path: '/' }))
export const useRouter = vi.fn(() => ({ push: vi.fn() }))
export const defineNuxtRouteMiddleware = (fn: Function) => fn
export const useI18n = vi.fn(() => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))
export const useSwitchLocalePath = vi.fn(() => (code: string) => `/${code}`)
export const definePageMeta = vi.fn()
export { ref, computed }