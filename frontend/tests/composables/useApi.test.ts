import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'

const mockBaseFetch = vi.fn()
const mockCreate = vi.fn(() => mockBaseFetch)

vi.stubGlobal('$fetch', Object.assign(vi.fn(), { create: mockCreate }))

const cookieValues: Record<string, ReturnType<typeof ref>> = {
  access_token: ref(null),
  refresh_token: ref(null),
}
vi.stubGlobal('useCookie', (name: string) => {
  if (!cookieValues[name]) cookieValues[name] = ref(null)
  return cookieValues[name]
})
vi.stubGlobal('useRuntimeConfig', () => ({
  public: { apiBase: '/api/v1' },
}))
vi.stubGlobal('useState', (_key: string, init?: () => any) => ref(init?.() ?? null))
vi.stubGlobal('navigateTo', vi.fn())

const { useApi } = await import('~/composables/useApi')

describe('useApi', () => {
  beforeEach(() => {
    mockBaseFetch.mockReset()
    mockCreate.mockClear()
    mockCreate.mockReturnValue(mockBaseFetch)
    cookieValues.access_token = ref('valid-token')
    cookieValues.refresh_token = ref('valid-refresh')
    vi.stubGlobal('useCookie', (name: string) => {
      if (!cookieValues[name]) cookieValues[name] = ref(null)
      return cookieValues[name]
    })
    vi.mocked(navigateTo).mockReset()
    vi.mocked($fetch).mockReset()
  })

  it('returns a function', () => {
    const api = useApi()
    expect(typeof api).toBe('function')
  })

  it('calls baseFetch with url and options', async () => {
    mockBaseFetch.mockResolvedValueOnce({ data: 'test' })
    const api = useApi()
    const result = await api('/test', { method: 'GET' })
    expect(mockBaseFetch).toHaveBeenCalledWith('/test', { method: 'GET' })
    expect(result).toEqual({ data: 'test' })
  })

  it('throws non-401 errors without retrying', async () => {
    const error = { response: { status: 500 }, message: 'Server Error' }
    mockBaseFetch.mockRejectedValueOnce(error)
    const api = useApi()
    await expect(api('/test')).rejects.toEqual(error)
  })

  it('throws 401 on auth endpoints without retrying', async () => {
    const error = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch.mockRejectedValueOnce(error)
    const api = useApi()
    await expect(api('/auth/login')).rejects.toEqual(error)
  })

  it('throws 401 on refresh endpoint without retrying', async () => {
    const error = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch.mockRejectedValueOnce(error)
    const api = useApi()
    await expect(api('/auth/refresh')).rejects.toEqual(error)
  })

  it('throws 401 when no refresh token', async () => {
    cookieValues.refresh_token.value = null
    const error = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch.mockRejectedValueOnce(error)
    const api = useApi()
    await expect(api('/test')).rejects.toEqual(error)
  })

  it('refreshes token and retries on 401', async () => {
    const error401 = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch
      .mockRejectedValueOnce(error401)
      .mockResolvedValueOnce({ data: 'retried' })

    vi.mocked($fetch).mockResolvedValueOnce({ token: 'new-token', refreshToken: 'new-refresh' })

    const api = useApi()
    const result = await api('/test')

    expect(vi.mocked($fetch)).toHaveBeenCalledWith('/api/v1/auth/refresh', {
      method: 'POST',
      body: { refreshToken: 'valid-refresh' },
    })
    expect(result).toEqual({ data: 'retried' })
  })

  it('clears tokens and navigates to login on refresh failure', async () => {
    const error401 = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch.mockRejectedValueOnce(error401)
    vi.mocked($fetch).mockRejectedValueOnce(new Error('Refresh failed'))

    const api = useApi()
    await expect(api('/test')).rejects.toEqual(error401)
    expect(navigateTo).toHaveBeenCalledWith('/login')
  })

  it('updates cookie values after successful token refresh', async () => {
    const error401 = { response: { status: 401 }, message: 'Unauthorized' }
    mockBaseFetch
      .mockRejectedValueOnce(error401)
      .mockResolvedValueOnce({ data: 'retried' })

    vi.mocked($fetch).mockResolvedValueOnce({ token: 'new-token', refreshToken: 'new-refresh' })

    const api = useApi()
    await api('/test')

    expect(cookieValues.access_token.value).toBe('new-token')
    expect(cookieValues.refresh_token.value).toBe('new-refresh')
  })

  it('passes baseURL config to $fetch.create', () => {
    useApi()
    expect(mockCreate).toHaveBeenCalledWith(
      expect.objectContaining({ baseURL: '/api/v1' }),
    )
  })

  it('configures onRequest hook in $fetch.create', () => {
    useApi()
    const createConfig = mockCreate.mock.calls[0][0]
    expect(createConfig.onRequest).toBeDefined()
    expect(typeof createConfig.onRequest).toBe('function')
  })

  it('deduplicates concurrent refresh calls', async () => {
    const error401 = { response: { status: 401 }, message: 'Unauthorized' }

    let resolveRefresh: Function
    const refreshPromise = new Promise(r => { resolveRefresh = r })
    vi.mocked($fetch).mockReturnValueOnce(refreshPromise as any)

    mockBaseFetch
      .mockRejectedValueOnce(error401)
      .mockRejectedValueOnce(error401)
      .mockResolvedValueOnce({ data: 'retry1' })
      .mockResolvedValueOnce({ data: 'retry2' })

    const api = useApi()
    const p1 = api('/test1')
    const p2 = api('/test2')

    resolveRefresh!({ token: 'new-token', refreshToken: 'new-refresh' })

    const [r1, r2] = await Promise.all([p1, p2])

    // Only one refresh call should have been made
    expect(vi.mocked($fetch)).toHaveBeenCalledTimes(1)
    expect(r1).toEqual({ data: 'retry1' })
    expect(r2).toEqual({ data: 'retry2' })
  })
})