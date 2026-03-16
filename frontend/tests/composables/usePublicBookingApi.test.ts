import { describe, it, expect, vi, beforeEach } from 'vitest'
import { usePublicBookingApi } from '~/composables/usePublicBookingApi'
import type { PublicShopInfo, PublicAvailableSlotsResponse, BookingResponse } from '~/types/booking'

const mockFetch = vi.fn()
vi.stubGlobal('$fetch', mockFetch)

describe('usePublicBookingApi', () => {
  beforeEach(() => {
    mockFetch.mockReset()
  })

  describe('getShopInfo', () => {
    it('fetches shop info by slug', async () => {
      const shopInfo: PublicShopInfo = {
        name: 'Test Shop',
        address: '123 Street',
        phone: '0901234567',
        workingHours: { monday: { open: '09:00', close: '18:00' }, sunday: null },
        services: [{ id: 'svc-1', name: 'Haircut', duration: 30, price: 100000 }],
      }
      mockFetch.mockResolvedValue(shopInfo)

      const api = usePublicBookingApi()
      const result = await api.getShopInfo('test-shop')

      expect(mockFetch).toHaveBeenCalledWith('/api/v1/public/shops/test-shop')
      expect(result).toEqual(shopInfo)
    })
  })

  describe('getAvailableSlots', () => {
    it('fetches available slots with query params', async () => {
      const response: PublicAvailableSlotsResponse = {
        date: '2026-03-16',
        slots: [
          { time: '09:00', available: true },
          { time: '09:30', available: false },
        ],
      }
      mockFetch.mockResolvedValue(response)

      const api = usePublicBookingApi()
      const result = await api.getAvailableSlots('test-shop', '2026-03-16', 'svc-1')

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/public/shops/test-shop/available-slots',
        { query: { date: '2026-03-16', serviceId: 'svc-1' } },
      )
      expect(result.slots).toHaveLength(2)
    })
  })

  describe('createBooking', () => {
    it('posts booking data and returns response', async () => {
      const response: BookingResponse = {
        appointment: {
          id: 'appt-1',
          date: '2026-03-16',
          time: '10:00',
          service: { id: 'svc-1', name: 'Haircut', duration: 30 },
          status: 'scheduled',
        },
        message: 'Đặt lịch thành công!',
      }
      mockFetch.mockResolvedValue(response)

      const api = usePublicBookingApi()
      const result = await api.createBooking('test-shop', {
        clientName: 'Nguyen Van A',
        clientPhone: '0901234567',
        serviceId: 'svc-1',
        date: '2026-03-16',
        time: '10:00',
      })

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/public/shops/test-shop/book',
        {
          method: 'POST',
          body: {
            clientName: 'Nguyen Van A',
            clientPhone: '0901234567',
            serviceId: 'svc-1',
            date: '2026-03-16',
            time: '10:00',
          },
        },
      )
      expect(result.appointment.id).toBe('appt-1')
      expect(result.appointment.status).toBe('scheduled')
    })

    it('throws on API error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'))

      const api = usePublicBookingApi()
      await expect(api.createBooking('test-shop', {
        clientName: 'Test',
        clientPhone: '0901234567',
        serviceId: 'svc-1',
        date: '2026-03-16',
        time: '10:00',
      })).rejects.toThrow('Network error')
    })
  })
})
