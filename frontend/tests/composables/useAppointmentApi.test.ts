import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const { useAppointmentApi } = await import('~/composables/useAppointmentApi')

describe('useAppointmentApi', () => {
  beforeEach(() => {
    mockApiFetch.mockReset()
  })

  describe('listAppointments', () => {
    it('calls GET /appointments/ with empty query when no filter', async () => {
      mockApiFetch.mockResolvedValueOnce({ data: [], cursor: null })
      const api = useAppointmentApi()
      await api.listAppointments()
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/', { query: {} })
    })

    it('passes all provided filter params', async () => {
      mockApiFetch.mockResolvedValueOnce({ data: [], cursor: null })
      const api = useAppointmentApi()
      await api.listAppointments({
        dateFrom: '2026-03-01',
        dateTo: '2026-03-31',
        status: ['scheduled', 'completed'],
        clientId: 'c-1',
        cursor: 'abc',
        limit: 10,
      })
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/', {
        query: {
          dateFrom: '2026-03-01',
          dateTo: '2026-03-31',
          status: ['scheduled', 'completed'],
          clientId: 'c-1',
          cursor: 'abc',
          limit: 10,
        },
      })
    })

    it('omits falsy/empty filter values', async () => {
      mockApiFetch.mockResolvedValueOnce({ data: [], cursor: null })
      const api = useAppointmentApi()
      await api.listAppointments({ status: [] })
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/', { query: {} })
    })
  })

  describe('getDailySchedule', () => {
    it('calls GET /appointments/daily with date query', async () => {
      mockApiFetch.mockResolvedValueOnce({ date: '2026-03-15', workingHours: null, appointments: [] })
      const api = useAppointmentApi()
      await api.getDailySchedule('2026-03-15')
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/daily', { query: { date: '2026-03-15' } })
    })
  })

  describe('getAppointment', () => {
    it('calls GET /appointments/:id', async () => {
      mockApiFetch.mockResolvedValueOnce({ id: 'appt-1' })
      const api = useAppointmentApi()
      const result = await api.getAppointment('appt-1')
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/appt-1')
      expect(result).toEqual({ id: 'appt-1' })
    })
  })

  describe('createAppointment', () => {
    it('calls POST /appointments/ with body', async () => {
      const body = { clientId: 'c-1', serviceId: 's-1', startTime: '2026-03-15T02:00:00Z', notes: null }
      mockApiFetch.mockResolvedValueOnce({ id: 'new-appt', ...body })
      const api = useAppointmentApi()
      await api.createAppointment(body)
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/', { method: 'POST', body })
    })
  })

  describe('updateAppointment', () => {
    it('calls PUT /appointments/:id with body', async () => {
      const body = { clientId: 'c-2' }
      mockApiFetch.mockResolvedValueOnce({ id: 'appt-1' })
      const api = useAppointmentApi()
      await api.updateAppointment('appt-1', body)
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/appt-1', { method: 'PUT', body })
    })
  })

  describe('changeStatus', () => {
    it('calls PATCH /appointments/:id/status with status body', async () => {
      mockApiFetch.mockResolvedValueOnce({ id: 'appt-1', status: 'completed' })
      const api = useAppointmentApi()
      await api.changeStatus('appt-1', 'completed')
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/appt-1/status', {
        method: 'PATCH',
        body: { status: 'completed' },
      })
    })
  })

  describe('cancelAppointment', () => {
    it('calls DELETE /appointments/:id', async () => {
      mockApiFetch.mockResolvedValueOnce({ id: 'appt-1', status: 'cancelled' })
      const api = useAppointmentApi()
      await api.cancelAppointment('appt-1')
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/appt-1', { method: 'DELETE' })
    })
  })

  describe('getAvailableSlots', () => {
    it('calls GET /appointments/available-slots with date and serviceId', async () => {
      mockApiFetch.mockResolvedValueOnce({ date: '2026-03-15', serviceDurationMinutes: 30, slots: [] })
      const api = useAppointmentApi()
      await api.getAvailableSlots('2026-03-15', 'svc-1')
      expect(mockApiFetch).toHaveBeenCalledWith('/appointments/available-slots', {
        query: { date: '2026-03-15', serviceId: 'svc-1' },
      })
    })
  })
})
