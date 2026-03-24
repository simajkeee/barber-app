export interface PublicService {
  id: string
  name: string
  duration: number
  price: number
}

export interface PublicShopInfo {
  name: string
  address: string
  phone: string
  workingHours: Record<string, { open: string; close: string } | null>
  services: PublicService[]
}

export interface AvailableSlot {
  time: string
  available: boolean
}

export interface PublicAvailableSlotsResponse {
  date: string
  slots: AvailableSlot[]
}

export interface BookingRequest {
  clientName: string
  clientPhone: string
  serviceId: string
  date: string
  time: string
  captchaToken: string
}

export interface BookingAppointment {
  id: string
  date: string
  time: string
  service: {
    id: string
    name: string
    duration: number
  }
  status: string
}

export interface BookingResponse {
  appointment: BookingAppointment
  message: string
}
