export interface Client {
  id: string
  firstName: string
  lastName: string
  phone: string
  email: string | null
  notes: string | null
  lastVisitAt: string | null
  visitCount: number
  createdAt: string
  updatedAt: string
}

export interface ClientListFilter {
  search?: string
  cursor?: string
  limit?: number
  sort?: 'created_at' | 'last_visit_at' | 'last_name'
  direction?: 'asc' | 'desc'
}

export interface ClientPagination {
  nextCursor: string | null
  hasMore: boolean
}

export interface ClientListResponse {
  data: Client[]
  pagination: ClientPagination
}

export interface CreateClientRequest {
  firstName: string
  lastName: string
  phone: string
  email?: string | null
  notes?: string | null
}

export interface UpdateClientRequest {
  firstName?: string
  lastName?: string
  phone?: string
  email?: string | null
  notes?: string | null
}