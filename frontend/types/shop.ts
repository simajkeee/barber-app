export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'

export interface ScheduleEntry {
  dayOfWeek: DayOfWeek
  openTime: string | null
  closeTime: string | null
  isOpen: boolean
}

export interface Shop {
  id: string
  name: string
  address: string
  phone: string
  description: string | null
  slug: string
  coverImageUrl: string | null
  schedule: ScheduleEntry[]
  createdAt: string
  updatedAt: string
}

export interface ShopService {
  id: string
  name: string
  durationMinutes: number
  price: number
  isActive: boolean
  sortOrder: number
  createdAt: string
  updatedAt: string
}

export interface CreateShopRequest {
  name: string
  address: string
  phone: string
  description?: string | null
}

export interface UpdateShopRequest {
  name?: string
  address?: string
  phone?: string
  description?: string | null
  slug?: string
  coverImageUrl?: string | null
}

export interface UpdateScheduleRequest {
  schedule: ScheduleEntry[]
}

export interface CreateServiceRequest {
  name: string
  durationMinutes: number
  price: number
  sortOrder?: number
}

export interface UpdateServiceRequest {
  name?: string
  durationMinutes?: number
  price?: number
  isActive?: boolean
  sortOrder?: number
}

export interface ShopResponse {
  shop: Shop
}

export interface ScheduleResponse {
  schedule: ScheduleEntry[]
}

export interface ServiceResponse {
  service: ShopService
}

export interface ServicesResponse {
  services: ShopService[]
}

export interface ScheduleEntryForm {
  dayOfWeek: DayOfWeek
  openTime: string
  closeTime: string
  isOpen: boolean
}