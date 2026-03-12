import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'
import { createUser } from '../factories'

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

let cookieValue = ref<string | null>(null)
vi.stubGlobal('useCookie', () => cookieValue)

const { useAuthStore: realUseAuthStore } = await import('~/stores/auth')
vi.stubGlobal('useAuthStore', realUseAuthStore)

const authMiddleware = (await import('~/middleware/auth')).default

describe('auth middleware', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    cookieValue = ref(null)
    vi.stubGlobal('useCookie', () => cookieValue)
    mockNavigateTo.mockReset()
    mockApiFetch.mockReset()
  })

  it('redirects to /login when no access token', async () => {
    cookieValue.value = null
    await authMiddleware()
    expect(mockNavigateTo).toHaveBeenCalledWith('/login')
  })

  it('fetches user when not initialized', async () => {
    cookieValue.value = 'some-token'
    mockApiFetch.mockResolvedValueOnce(createUser())

    await authMiddleware()

    const store = realUseAuthStore()
    expect(store.isInitialized).toBe(true)
    expect(mockNavigateTo).not.toHaveBeenCalled()
  })

  it('skips fetch when already initialized', async () => {
    cookieValue.value = 'some-token'
    const store = realUseAuthStore()
    store.setUser(createUser())

    await authMiddleware()

    expect(mockApiFetch).not.toHaveBeenCalled()
    expect(mockNavigateTo).not.toHaveBeenCalled()
  })

  it('redirects to /login on fetch failure', async () => {
    cookieValue.value = 'some-token'
    mockApiFetch.mockRejectedValueOnce(new Error('Failed'))

    await authMiddleware()

    expect(mockNavigateTo).toHaveBeenCalledWith('/login')
  })
})