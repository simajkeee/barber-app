import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const { useClientApi } = await import('~/composables/useClientApi')

describe('useClientApi', () => {
  beforeEach(() => {
    mockApiFetch.mockReset()
  })

  it('listClients calls GET /clients/ without query when no filter', async () => {
    mockApiFetch.mockResolvedValueOnce({ data: [], pagination: { nextCursor: null, hasMore: false } })

    const api = useClientApi()
    await api.listClients()

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/', { query: {} })
  })

  it('listClients passes all filter params as query', async () => {
    mockApiFetch.mockResolvedValueOnce({ data: [], pagination: { nextCursor: null, hasMore: false } })

    const api = useClientApi()
    await api.listClients({ search: 'Nguyen', cursor: 'abc', limit: 10, sort: 'last_name', direction: 'asc' })

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/', {
      query: { search: 'Nguyen', cursor: 'abc', limit: 10, sort: 'last_name', direction: 'asc' },
    })
  })

  it('listClients omits falsy filter values from query', async () => {
    mockApiFetch.mockResolvedValueOnce({ data: [], pagination: { nextCursor: null, hasMore: false } })

    const api = useClientApi()
    await api.listClients({ search: '', sort: 'created_at', direction: 'desc' })

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/', {
      query: { sort: 'created_at', direction: 'desc' },
    })
  })

  it('getClient calls GET /clients/:id', async () => {
    const response = { id: 'c-1', firstName: 'Test' }
    mockApiFetch.mockResolvedValueOnce(response)

    const api = useClientApi()
    const result = await api.getClient('c-1')

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/c-1')
    expect(result).toEqual(response)
  })

  it('createClient calls POST /clients/', async () => {
    const body = { firstName: 'A', lastName: 'B', phone: '123' }
    const response = { id: 'c-new', ...body }
    mockApiFetch.mockResolvedValueOnce(response)

    const api = useClientApi()
    const result = await api.createClient(body)

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/', { method: 'POST', body })
    expect(result).toEqual(response)
  })

  it('updateClient calls PUT /clients/:id', async () => {
    const body = { firstName: 'Updated' }
    mockApiFetch.mockResolvedValueOnce({ id: 'c-1', ...body })

    const api = useClientApi()
    await api.updateClient('c-1', body)

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/c-1', { method: 'PUT', body })
  })

  it('deleteClient calls DELETE /clients/:id', async () => {
    mockApiFetch.mockResolvedValueOnce(undefined)

    const api = useClientApi()
    await api.deleteClient('c-1')

    expect(mockApiFetch).toHaveBeenCalledWith('/clients/c-1', { method: 'DELETE' })
  })
})