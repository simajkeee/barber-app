import type {
  AuthResponse,
  AuthResult,
  LoginRequest,
  RegisterRequest,
  UpdateProfileRequest,
  User,
  ApiValidationError,
} from '~/types/auth'
import { FetchError } from 'ofetch'

export function useAuth() {
  const api = useApi()
  const store = useAuthStore()
  const accessToken = useCookie('access_token')
  const refreshToken = useCookie('refresh_token', { maxAge: 60 * 60 * 24 * 30 })
  const localePath = useLocalePath()

  const user = computed(() => store.user)
  const isAuthenticated = computed(() => store.isAuthenticated)

  function storeTokens(token: string, refresh: string) {
    accessToken.value = token
    refreshToken.value = refresh
  }

  function clearTokens() {
    accessToken.value = null
    refreshToken.value = null
  }

  function handleAuthResponse(response: AuthResponse) {
    storeTokens(response.token, response.refreshToken)
    store.setUser(response.user)
  }

  function parseError(err: unknown): AuthResult {
    if (!(err instanceof FetchError) || !err.data) {
      return { success: false, error: 'unexpected' }
    }

    const data = err.data as ApiValidationError
    const fieldErrors: Record<string, string> | undefined = data.details?.length
      ? Object.fromEntries(data.details.map((d) => [d.field, d.message]))
      : undefined

    return { success: false, error: data.code ?? data.message, fieldErrors }
  }

  async function login(credentials: LoginRequest): Promise<AuthResult> {
    try {
      const response = await api<AuthResponse>('/auth/login', {
        method: 'POST',
        body: credentials,
      })
      handleAuthResponse(response)
      return { success: true }
    } catch (err) {
      return parseError(err)
    }
  }

  async function register(data: RegisterRequest): Promise<AuthResult> {
    try {
      const response = await api<AuthResponse>('/auth/register', {
        method: 'POST',
        body: data,
      })
      handleAuthResponse(response)
      return { success: true }
    } catch (err) {
      return parseError(err)
    }
  }

  async function facebookLogin(
    fbAccessToken: string,
  ): Promise<AuthResult & { isNewUser?: boolean }> {
    try {
      const response = await api<AuthResponse>('/auth/facebook', {
        method: 'POST',
        body: { accessToken: fbAccessToken },
      })
      handleAuthResponse(response)
      return { success: true, isNewUser: response.isNewUser }
    } catch (err) {
      return parseError(err)
    }
  }

  async function forgotPassword(email: string): Promise<AuthResult> {
    try {
      await api('/auth/forgot-password', {
        method: 'POST',
        body: { email },
      })
      return { success: true }
    } catch (err) {
      return parseError(err)
    }
  }

  async function resetPassword(token: string, password: string): Promise<AuthResult> {
    try {
      await api('/auth/reset-password', {
        method: 'POST',
        body: { token, password },
      })
      return { success: true }
    } catch (err) {
      return parseError(err)
    }
  }

  async function logout() {
    clearTokens()
    store.clear()
    await navigateTo(localePath('/login'))
  }

  async function updateProfile(data: UpdateProfileRequest): Promise<User> {
    const updated = await api<User>('/auth/me', {
      method: 'PUT',
      body: data,
    })
    store.setUser(updated)
    return updated
  }

  return {
    user,
    isAuthenticated,
    login,
    register,
    facebookLogin,
    forgotPassword,
    resetPassword,
    logout,
    updateProfile,
  }
}