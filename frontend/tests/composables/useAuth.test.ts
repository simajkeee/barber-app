import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'
import { FetchError } from 'ofetch'
import { createUser, createAuthResponse } from '../factories'
import type { ApiValidationError } from '~/types/auth'

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const cookieStore: Record<string, ReturnType<typeof ref>> = {}
vi.stubGlobal('useCookie', (name: string) => {
  if (!cookieStore[name]) cookieStore[name] = ref(null)
  return cookieStore[name]
})

const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

const { useAuthStore: realUseAuthStore } = await import('~/stores/auth')
vi.stubGlobal('useAuthStore', realUseAuthStore)

const { useAuth } = await import('~/composables/useAuth')

function createFetchError(status: number, data: any): FetchError {
  const err = new FetchError('Fetch error')
  err.data = data
  ;(err as any).response = { status }
  return err
}

describe('useAuth', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockApiFetch.mockReset()
    mockNavigateTo.mockReset()
    cookieStore.access_token = ref(null)
    cookieStore.refresh_token = ref(null)
  })

  describe('login', () => {
    it('stores tokens and user on success', async () => {
      const authResponse = createAuthResponse()
      mockApiFetch.mockResolvedValueOnce(authResponse)

      const { login } = useAuth()
      const result = await login({ email: 'test@example.com', password: 'password123' })

      expect(result).toEqual({ success: true })
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/login', {
        method: 'POST',
        body: { email: 'test@example.com', password: 'password123' },
      })
      expect(cookieStore.access_token.value).toBe('access-token-123')
      expect(cookieStore.refresh_token.value).toBe('refresh-token-456')
      const store = realUseAuthStore()
      expect(store.user).toEqual(authResponse.user)
    })

    it('returns error code on INVALID_CREDENTIALS', async () => {
      const apiError: ApiValidationError = { code: 'INVALID_CREDENTIALS', message: 'Bad credentials' }
      mockApiFetch.mockRejectedValueOnce(createFetchError(401, apiError))

      const { login } = useAuth()
      const result = await login({ email: 'test@example.com', password: 'wrong' })

      expect(result).toEqual({ success: false, error: 'INVALID_CREDENTIALS', fieldErrors: undefined })
    })

    it('returns field errors from validation response', async () => {
      const apiError: ApiValidationError = {
        code: 'VALIDATION_ERROR',
        message: 'Validation failed',
        details: [{ field: 'email', message: 'Email is invalid' }],
      }
      mockApiFetch.mockRejectedValueOnce(createFetchError(422, apiError))

      const { login } = useAuth()
      const result = await login({ email: 'bad', password: 'password123' })

      expect(result).toEqual({
        success: false,
        error: 'VALIDATION_ERROR',
        fieldErrors: { email: 'Email is invalid' },
      })
    })

    it('returns unexpected error for non-FetchError', async () => {
      mockApiFetch.mockRejectedValueOnce(new Error('Network error'))

      const { login } = useAuth()
      const result = await login({ email: 'test@example.com', password: 'password123' })

      expect(result).toEqual({ success: false, error: 'unexpected' })
    })
  })

  describe('register', () => {
    it('stores tokens and user on success', async () => {
      const authResponse = createAuthResponse()
      mockApiFetch.mockResolvedValueOnce(authResponse)

      const { register } = useAuth()
      const result = await register({
        email: 'test@example.com',
        password: 'password123',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '0901234567',
      })

      expect(result).toEqual({ success: true })
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/register', {
        method: 'POST',
        body: { email: 'test@example.com', password: 'password123', firstName: 'John', lastName: 'Doe', phoneNumber: '0901234567' },
      })
      const store = realUseAuthStore()
      expect(store.user).toEqual(authResponse.user)
    })

    it('returns EMAIL_ALREADY_EXISTS error', async () => {
      const apiError: ApiValidationError = { code: 'EMAIL_ALREADY_EXISTS', message: 'Email taken' }
      mockApiFetch.mockRejectedValueOnce(createFetchError(409, apiError))

      const { register } = useAuth()
      const result = await register({
        email: 'existing@example.com',
        password: 'password123',
        firstName: 'John',
        lastName: 'Doe',
        phoneNumber: '0901234567',
      })

      expect(result).toEqual({ success: false, error: 'EMAIL_ALREADY_EXISTS', fieldErrors: undefined })
    })
  })

  describe('facebookLogin', () => {
    it('stores tokens and returns isNewUser on success', async () => {
      const authResponse = createAuthResponse({ isNewUser: true })
      mockApiFetch.mockResolvedValueOnce(authResponse)

      const { facebookLogin } = useAuth()
      const result = await facebookLogin('fb-token-123')

      expect(result).toEqual({ success: true, isNewUser: true })
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/facebook', {
        method: 'POST',
        body: { accessToken: 'fb-token-123' },
      })
    })

    it('returns error on failure', async () => {
      mockApiFetch.mockRejectedValueOnce(createFetchError(400, { code: 'FACEBOOK_AUTH_FAILED', message: 'Invalid token' }))

      const { facebookLogin } = useAuth()
      const result = await facebookLogin('bad-token')

      expect(result).toEqual({ success: false, error: 'FACEBOOK_AUTH_FAILED', fieldErrors: undefined })
    })
  })

  describe('logout', () => {
    it('clears tokens, store, and navigates to login', async () => {
      cookieStore.access_token.value = 'some-token'
      cookieStore.refresh_token.value = 'some-refresh'
      const store = realUseAuthStore()
      store.setUser(createUser())

      const { logout } = useAuth()
      await logout()

      expect(cookieStore.access_token.value).toBeNull()
      expect(cookieStore.refresh_token.value).toBeNull()
      expect(store.user).toBeNull()
      expect(mockNavigateTo).toHaveBeenCalledWith('/login')
    })
  })

  describe('updateProfile', () => {
    it('updates user in store and returns updated user', async () => {
      const updatedUser = createUser({ firstName: 'Updated' })
      mockApiFetch.mockResolvedValueOnce(updatedUser)

      const { updateProfile } = useAuth()
      const result = await updateProfile({ firstName: 'Updated' })

      expect(result).toEqual(updatedUser)
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/me', {
        method: 'PUT',
        body: { firstName: 'Updated' },
      })
      const store = realUseAuthStore()
      expect(store.user).toEqual(updatedUser)
    })

    it('throws on API failure', async () => {
      mockApiFetch.mockRejectedValueOnce(new Error('Network error'))

      const { updateProfile } = useAuth()
      await expect(updateProfile({ firstName: 'Updated' })).rejects.toThrow('Network error')
    })
  })

  describe('forgotPassword', () => {
    it('calls forgot-password endpoint and returns success', async () => {
      mockApiFetch.mockResolvedValueOnce({})

      const { forgotPassword } = useAuth()
      const result = await forgotPassword('test@example.com')

      expect(result).toEqual({ success: true })
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/forgot-password', {
        method: 'POST',
        body: { email: 'test@example.com' },
      })
    })

    it('returns error code on RATE_LIMIT_EXCEEDED', async () => {
      const apiError: ApiValidationError = { code: 'RATE_LIMIT_EXCEEDED', message: 'Too many requests' }
      mockApiFetch.mockRejectedValueOnce(createFetchError(429, apiError))

      const { forgotPassword } = useAuth()
      const result = await forgotPassword('test@example.com')

      expect(result).toEqual({ success: false, error: 'RATE_LIMIT_EXCEEDED', fieldErrors: undefined })
    })

    it('returns unexpected error for non-FetchError', async () => {
      mockApiFetch.mockRejectedValueOnce(new Error('Network error'))

      const { forgotPassword } = useAuth()
      const result = await forgotPassword('test@example.com')

      expect(result).toEqual({ success: false, error: 'unexpected' })
    })

    it('does not store tokens or set user', async () => {
      mockApiFetch.mockResolvedValueOnce({})

      const { forgotPassword } = useAuth()
      await forgotPassword('test@example.com')

      expect(cookieStore.access_token.value).toBeNull()
      expect(cookieStore.refresh_token.value).toBeNull()
      const store = realUseAuthStore()
      expect(store.user).toBeNull()
    })
  })

  describe('resetPassword', () => {
    it('calls reset-password endpoint and returns success', async () => {
      mockApiFetch.mockResolvedValueOnce({})

      const { resetPassword } = useAuth()
      const result = await resetPassword('token-abc', 'newPassword123')

      expect(result).toEqual({ success: true })
      expect(mockApiFetch).toHaveBeenCalledWith('/auth/reset-password', {
        method: 'POST',
        body: { token: 'token-abc', password: 'newPassword123' },
      })
    })

    it('returns error code on INVALID_RESET_TOKEN', async () => {
      const apiError: ApiValidationError = { code: 'INVALID_RESET_TOKEN', message: 'Invalid token' }
      mockApiFetch.mockRejectedValueOnce(createFetchError(401, apiError))

      const { resetPassword } = useAuth()
      const result = await resetPassword('bad-token', 'newPassword123')

      expect(result).toEqual({ success: false, error: 'INVALID_RESET_TOKEN', fieldErrors: undefined })
    })

    it('returns field errors from validation response', async () => {
      const apiError: ApiValidationError = {
        code: 'VALIDATION_ERROR',
        message: 'Validation failed',
        details: [{ field: 'password', message: 'Password too common' }],
      }
      mockApiFetch.mockRejectedValueOnce(createFetchError(400, apiError))

      const { resetPassword } = useAuth()
      const result = await resetPassword('token-abc', 'weak')

      expect(result).toEqual({
        success: false,
        error: 'VALIDATION_ERROR',
        fieldErrors: { password: 'Password too common' },
      })
    })

    it('returns unexpected error for non-FetchError', async () => {
      mockApiFetch.mockRejectedValueOnce(new Error('Network error'))

      const { resetPassword } = useAuth()
      const result = await resetPassword('token-abc', 'newPassword123')

      expect(result).toEqual({ success: false, error: 'unexpected' })
    })

    it('does not store tokens or set user', async () => {
      mockApiFetch.mockResolvedValueOnce({})

      const { resetPassword } = useAuth()
      await resetPassword('token-abc', 'newPassword123')

      expect(cookieStore.access_token.value).toBeNull()
      expect(cookieStore.refresh_token.value).toBeNull()
      const store = realUseAuthStore()
      expect(store.user).toBeNull()
    })
  })

  describe('computed properties', () => {
    it('user reflects store state', () => {
      const store = realUseAuthStore()
      const { user } = useAuth()
      expect(user.value).toBeNull()
      store.setUser(createUser())
      expect(user.value).toEqual(createUser())
    })

    it('isAuthenticated reflects store state', () => {
      const store = realUseAuthStore()
      const { isAuthenticated } = useAuth()
      expect(isAuthenticated.value).toBe(false)
      store.setUser(createUser())
      expect(isAuthenticated.value).toBe(true)
    })
  })
})