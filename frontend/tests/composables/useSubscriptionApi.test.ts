import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useSubscriptionApi } from '~/composables/useSubscriptionApi'
import { createSubscriptionResponse } from '../factories'

describe('useSubscriptionApi', () => {
  const mockApiFetch = vi.fn()

  beforeEach(() => {
    vi.stubGlobal('useApi', () => mockApiFetch)
    mockApiFetch.mockReset()
  })

  describe('getSubscription', () => {
    it('fetches subscription from correct endpoint', async () => {
      const response = createSubscriptionResponse()
      mockApiFetch.mockResolvedValue(response)

      const { getSubscription } = useSubscriptionApi()
      const result = await getSubscription()

      expect(mockApiFetch).toHaveBeenCalledWith('/subscription/')
      expect(result).toEqual(response)
    })

    it('returns pro subscription with daysRemaining', async () => {
      const response = createSubscriptionResponse({
        plan: 'pro',
        endDate: '2026-04-14T00:00:00+07:00',
        daysRemaining: 25,
        usage: {
          appointmentsThisMonth: 67,
          appointmentLimit: null,
          limitReached: false,
        },
      })
      mockApiFetch.mockResolvedValue(response)

      const { getSubscription } = useSubscriptionApi()
      const result = await getSubscription()

      expect(result.plan).toBe('pro')
      expect(result.daysRemaining).toBe(25)
      expect(result.usage.appointmentLimit).toBeNull()
    })
  })
})
