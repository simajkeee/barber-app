export type AppointmentStatus = 'scheduled' | 'completed' | 'cancelled' | 'no_show'

export interface AppointmentClient {
  id: string
  firstName: string
  lastName: string
  phone: string
}

export interface AppointmentService {
  id: string
  name: string
  durationMinutes: number
  price: number
}

export interface Appointment {
  id: string
  client: AppointmentClient
  service: AppointmentService
  startTime: string
  endTime: string
  status: AppointmentStatus
  notes: string | null
  createdAt: string
  updatedAt: string
}

export interface AppointmentListFilter {
  dateFrom?: string
  dateTo?: string
  status?: AppointmentStatus[]
  clientId?: string
  cursor?: string
  limit?: number
}

export interface AppointmentListResponse {
  data: Appointment[]
  cursor: string | null
}

export interface DailyScheduleResponse {
  date: string
  workingHours: { openTime: string; closeTime: string } | null
  appointments: Appointment[]
}

export interface TimeSlot {
  startTime: string
  endTime: string
}

export interface AvailableSlotsResponse {
  date: string
  serviceDurationMinutes: number
  slots: TimeSlot[]
}

export interface CreateAppointmentRequest {
  clientId: string
  serviceId: string
  startTime: string
  notes?: string | null
}

export interface UpdateAppointmentRequest {
  clientId?: string
  serviceId?: string
  startTime?: string
  notes?: string | null
}
