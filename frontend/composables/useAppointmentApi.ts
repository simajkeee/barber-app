import type {
  Appointment,
  AppointmentListFilter,
  AppointmentListResponse,
  AppointmentStatus,
  AvailableSlotsResponse,
  CreateAppointmentRequest,
  DailyScheduleResponse,
  UpdateAppointmentRequest,
} from '~/types/appointment'

export function useAppointmentApi() {
  const api = useApi()

  async function listAppointments(filter?: AppointmentListFilter): Promise<AppointmentListResponse> {
    const query: Record<string, string | number | string[] | undefined> = {}
    if (filter?.dateFrom) query.dateFrom = filter.dateFrom
    if (filter?.dateTo) query.dateTo = filter.dateTo
    if (filter?.status?.length) query.status = filter.status
    if (filter?.clientId) query.clientId = filter.clientId
    if (filter?.cursor) query.cursor = filter.cursor
    if (filter?.limit) query.limit = filter.limit

    return api<AppointmentListResponse>('/appointments/', { query })
  }

  async function getDailySchedule(date: string): Promise<DailyScheduleResponse> {
    return api<DailyScheduleResponse>('/appointments/daily', { query: { date } })
  }

  async function getAppointment(id: string): Promise<Appointment> {
    return api<Appointment>(`/appointments/${id}`)
  }

  async function createAppointment(data: CreateAppointmentRequest): Promise<Appointment> {
    return api<Appointment>('/appointments/', { method: 'POST', body: data })
  }

  async function updateAppointment(id: string, data: UpdateAppointmentRequest): Promise<Appointment> {
    return api<Appointment>(`/appointments/${id}`, { method: 'PUT', body: data })
  }

  async function changeStatus(id: string, status: AppointmentStatus): Promise<Appointment> {
    return api<Appointment>(`/appointments/${id}/status`, { method: 'PATCH', body: { status } })
  }

  async function cancelAppointment(id: string): Promise<Appointment> {
    return api<Appointment>(`/appointments/${id}`, { method: 'DELETE' })
  }

  async function getAvailableSlots(date: string, serviceId: string): Promise<AvailableSlotsResponse> {
    return api<AvailableSlotsResponse>('/appointments/available-slots', {
      query: { date, serviceId },
    })
  }

  return {
    listAppointments,
    getDailySchedule,
    getAppointment,
    createAppointment,
    updateAppointment,
    changeStatus,
    cancelAppointment,
    getAvailableSlots,
  }
}
