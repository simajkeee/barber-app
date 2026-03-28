import type { TokenRefreshResponse } from '~/types/auth'

export function useApi() {
  const config = useRuntimeConfig()
  const accessToken = useCookie('access_token')
  const refreshToken = useCookie('refresh_token', { maxAge: 60 * 60 * 24 * 30 })
  const localePath = useLocalePath()

  // SSR-safe: scoped per request via useState, not module-level
  const refreshPromise = useState<Promise<TokenRefreshResponse> | null>('api-refresh-promise', () => null)

  async function attemptRefresh(): Promise<TokenRefreshResponse> {
    if (!refreshPromise.value) {
      refreshPromise.value = $fetch<TokenRefreshResponse>(
        `${config.public.apiBase}/auth/refresh`,
        {
          method: 'POST',
          body: { refreshToken: refreshToken.value },
        },
      ).finally(() => {
        refreshPromise.value = null
      })
    }
    return refreshPromise.value
  }

  const baseFetch = $fetch.create({
    baseURL: config.public.apiBase,
    onRequest({ options }) {
      if (accessToken.value) {
        options.headers.set('Authorization', `Bearer ${accessToken.value}`)
      }
    },
  })

  return async function apiFetch<T>(url: string, opts?: Parameters<typeof baseFetch>[1]): Promise<T> {
    try {
      return await baseFetch<T>(url, opts) as T
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number } })?.response?.status
      if (status !== 401 || !refreshToken.value) throw err

      // Don't retry auth endpoints
      if (url.includes('/auth/refresh') || url.includes('/auth/login')) throw err

      try {
        const tokens = await attemptRefresh()
        accessToken.value = tokens.token
        refreshToken.value = tokens.refreshToken

        const retryHeaders = new Headers(opts?.headers)
        retryHeaders.set('Authorization', `Bearer ${tokens.token}`)
        return await baseFetch<T>(url, { ...opts, headers: retryHeaders }) as T
      } catch {
        accessToken.value = null
        refreshToken.value = null
        await navigateTo(localePath('/login'))
        throw err
      }
    }
  }
}