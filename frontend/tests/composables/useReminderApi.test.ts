import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useReminderApi } from '~/composables/useReminderApi'
import { createReminderTodayResponse, createReminderSettings } from '~/tests/factories'

describe('useReminderApi', () => {
  const mockApi = vi.fn()

  beforeEach(() => {
    vi.stubGlobal('useApi', () => mockApi)
    mockApi.mockReset()
  })

  describe('getTodayReminders', () => {
    it('calls /reminders/today with no params', async () => {
      const response = createReminderTodayResponse()
      mockApi.mockResolvedValue(response)

      const api = useReminderApi()
      const result = await api.getTodayReminders()

      expect(mockApi).toHaveBeenCalledWith('/reminders/today', { query: {} })
      expect(result).toEqual(response)
    })

    it('passes limit and cursor as query params', async () => {
      mockApi.mockResolvedValue(createReminderTodayResponse())

      const api = useReminderApi()
      await api.getTodayReminders({ limit: 10, cursor: 'abc123' })

      expect(mockApi).toHaveBeenCalledWith('/reminders/today', {
        query: { limit: 10, cursor: 'abc123' },
      })
    })
  })

  describe('getSettings', () => {
    it('calls /reminders/settings', async () => {
      const settings = createReminderSettings()
      mockApi.mockResolvedValue(settings)

      const api = useReminderApi()
      const result = await api.getSettings()

      expect(mockApi).toHaveBeenCalledWith('/reminders/settings')
      expect(result).toEqual(settings)
    })
  })

  describe('updateSettings', () => {
    it('PUTs to /reminders/settings', async () => {
      const settings = createReminderSettings({ daysSinceLastVisit: 14 })
      mockApi.mockResolvedValue(settings)

      const api = useReminderApi()
      const result = await api.updateSettings({ daysSinceLastVisit: 14 })

      expect(mockApi).toHaveBeenCalledWith('/reminders/settings', {
        method: 'PUT',
        body: { daysSinceLastVisit: 14 },
      })
      expect(result).toEqual(settings)
    })
  })

  describe('markReminded', () => {
    it('POSTs to /reminders/:clientId/mark-reminded', async () => {
      const response = { clientId: 'client-1', lastRemindedAt: '2026-03-15T10:00:00Z' }
      mockApi.mockResolvedValue(response)

      const api = useReminderApi()
      const result = await api.markReminded('client-1')

      expect(mockApi).toHaveBeenCalledWith('/reminders/client-1/mark-reminded', { method: 'POST' })
      expect(result).toEqual(response)
    })
  })
})
