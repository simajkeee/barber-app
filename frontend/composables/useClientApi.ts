import type {
  Client,
  ClientListFilter,
  ClientListResponse,
  CreateClientRequest,
  UpdateClientRequest,
} from '~/types/client'

export function useClientApi() {
  const api = useApi()

  async function listClients(filter?: ClientListFilter): Promise<ClientListResponse> {
    const query: Record<string, string | number> = {}
    if (filter?.search) query.search = filter.search
    if (filter?.cursor) query.cursor = filter.cursor
    if (filter?.limit) query.limit = filter.limit
    if (filter?.sort) query.sort = filter.sort
    if (filter?.direction) query.direction = filter.direction

    return api<ClientListResponse>('/clients/', { query })
  }

  async function getClient(id: string): Promise<Client> {
    return api<Client>(`/clients/${id}`)
  }

  async function createClient(data: CreateClientRequest): Promise<Client> {
    return api<Client>('/clients/', { method: 'POST', body: data })
  }

  async function updateClient(id: string, data: UpdateClientRequest): Promise<Client> {
    return api<Client>(`/clients/${id}`, { method: 'PUT', body: data })
  }

  async function deleteClient(id: string): Promise<void> {
    await api<void>(`/clients/${id}`, { method: 'DELETE' })
  }

  return {
    listClients,
    getClient,
    createClient,
    updateClient,
    deleteClient,
  }
}