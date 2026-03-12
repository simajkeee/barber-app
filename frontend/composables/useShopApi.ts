import type {
  CreateShopRequest,
  UpdateShopRequest,
  UpdateScheduleRequest,
  CreateServiceRequest,
  UpdateServiceRequest,
  ShopResponse,
  ScheduleResponse,
  ServiceResponse,
  ServicesResponse,
} from '~/types/shop'

export function useShopApi() {
  const api = useApi()

  async function createShop(data: CreateShopRequest): Promise<ShopResponse> {
    return api<ShopResponse>('/shops/', { method: 'POST', body: data })
  }

  async function getShop(): Promise<ShopResponse> {
    return api<ShopResponse>('/shops/me')
  }

  async function updateShop(data: UpdateShopRequest): Promise<ShopResponse> {
    return api<ShopResponse>('/shops/me', { method: 'PUT', body: data })
  }

  async function updateSchedule(data: UpdateScheduleRequest): Promise<ScheduleResponse> {
    return api<ScheduleResponse>('/shops/me/schedule', { method: 'PUT', body: data })
  }

  async function fetchServices(includeInactive = false): Promise<ServicesResponse> {
    return api<ServicesResponse>('/shops/me/services', {
      ...(includeInactive && { query: { includeInactive: 'true' } }),
    })
  }

  async function createService(data: CreateServiceRequest): Promise<ServiceResponse> {
    return api<ServiceResponse>('/shops/me/services', { method: 'POST', body: data })
  }

  async function updateService(id: string, data: UpdateServiceRequest): Promise<ServiceResponse> {
    return api<ServiceResponse>(`/shops/me/services/${id}`, { method: 'PUT', body: data })
  }

  async function deleteService(id: string): Promise<void> {
    await api<void>(`/shops/me/services/${id}`, { method: 'DELETE' })
  }

  return {
    createShop,
    getShop,
    updateShop,
    updateSchedule,
    fetchServices,
    createService,
    updateService,
    deleteService,
  }
}