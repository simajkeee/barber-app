import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'

const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

let cookieValue = ref<string | null>(null)
vi.stubGlobal('useCookie', () => cookieValue)
vi.stubGlobal('defineNuxtRouteMiddleware', (fn: Function) => fn)

const guestMiddleware = (await import('~/middleware/guest')).default

describe('guest middleware', () => {
  beforeEach(() => {
    cookieValue = ref(null)
    vi.stubGlobal('useCookie', () => cookieValue)
    mockNavigateTo.mockReset()
  })

  it('redirects to /dashboard when access token exists', () => {
    cookieValue.value = 'some-token'
    guestMiddleware()
    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard')
  })

  it('does nothing when no access token', () => {
    cookieValue.value = null
    guestMiddleware()
    expect(mockNavigateTo).not.toHaveBeenCalled()
  })
})