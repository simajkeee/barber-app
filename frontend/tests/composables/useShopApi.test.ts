import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const { useShopApi } = await import('~/composables/useShopApi')

describe('useShopApi', () => {
  beforeEach(() => {
    mockApiFetch.mockReset()
  })

  it('createShop calls POST /shops/', async () => {
    const body = { name: 'Shop', address: '123 St', phone: '0901234567' }
    const response = { shop: { id: '1', ...body } }
    mockApiFetch.mockResolvedValueOnce(response)

    const api = useShopApi()
    const result = await api.createShop(body)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/', { method: 'POST', body })
    expect(result).toEqual(response)
  })

  it('getShop calls GET /shops/me', async () => {
    const response = { shop: { id: '1', name: 'Shop' } }
    mockApiFetch.mockResolvedValueOnce(response)

    const api = useShopApi()
    const result = await api.getShop()

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me')
    expect(result).toEqual(response)
  })

  it('updateShop calls PUT /shops/me', async () => {
    const body = { name: 'Updated Shop' }
    mockApiFetch.mockResolvedValueOnce({ shop: { id: '1', ...body } })

    const api = useShopApi()
    await api.updateShop(body)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me', { method: 'PUT', body })
  })

  it('updateSchedule calls PUT /shops/me/schedule', async () => {
    const body = { schedule: [{ dayOfWeek: 'monday', openTime: '09:00', closeTime: '18:00', isOpen: true }] }
    mockApiFetch.mockResolvedValueOnce({ schedule: body.schedule })

    const api = useShopApi()
    await api.updateSchedule(body as any)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/schedule', { method: 'PUT', body })
  })

  it('fetchServices calls GET /shops/me/services without query by default', async () => {
    mockApiFetch.mockResolvedValueOnce({ services: [] })

    const api = useShopApi()
    await api.fetchServices()

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/services', {})
  })

  it('fetchServices passes includeInactive query when true', async () => {
    mockApiFetch.mockResolvedValueOnce({ services: [] })

    const api = useShopApi()
    await api.fetchServices(true)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/services', {
      query: { includeInactive: 'true' },
    })
  })

  it('createService calls POST /shops/me/services', async () => {
    const body = { name: 'Haircut', durationMinutes: 30, price: 100000 }
    mockApiFetch.mockResolvedValueOnce({ service: { id: '1', ...body } })

    const api = useShopApi()
    await api.createService(body)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/services', { method: 'POST', body })
  })

  it('updateService calls PUT /shops/me/services/:id', async () => {
    const body = { name: 'Updated Cut' }
    mockApiFetch.mockResolvedValueOnce({ service: { id: 'svc-1', ...body } })

    const api = useShopApi()
    await api.updateService('svc-1', body)

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/services/svc-1', { method: 'PUT', body })
  })

  it('deleteService calls DELETE /shops/me/services/:id', async () => {
    mockApiFetch.mockResolvedValueOnce(undefined)

    const api = useShopApi()
    await api.deleteService('svc-1')

    expect(mockApiFetch).toHaveBeenCalledWith('/shops/me/services/svc-1', { method: 'DELETE' })
  })
})