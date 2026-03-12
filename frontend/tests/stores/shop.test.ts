import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { FetchError } from 'ofetch'
import { createShop, createDefaultSchedule } from '../factories'

const mockGetShop = vi.fn()
vi.stubGlobal('useShopApi', () => ({ getShop: mockGetShop }))

const { useShopStore } = await import('~/stores/shop')

function createFetchError(status: number): FetchError {
  const err = new FetchError('Fetch error')
  ;(err as any).response = { status }
  return err
}

describe('useShopStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockGetShop.mockReset()
  })

  describe('initial state', () => {
    it('starts with null shop and empty schedule', () => {
      const store = useShopStore()
      expect(store.shop).toBeNull()
      expect(store.schedule).toEqual([])
      expect(store.isLoading).toBe(false)
      expect(store.error).toBeNull()
    })
  })

  describe('computed', () => {
    it('hasShop is false when no shop', () => {
      const store = useShopStore()
      expect(store.hasShop).toBe(false)
    })

    it('hasShop is true when shop exists', () => {
      const store = useShopStore()
      store.setShop(createShop())
      expect(store.hasShop).toBe(true)
    })

    it('shopName returns name or empty string', () => {
      const store = useShopStore()
      expect(store.shopName).toBe('')
      store.setShop(createShop({ name: 'My Shop' }))
      expect(store.shopName).toBe('My Shop')
    })
  })

  describe('fetchShop', () => {
    it('sets shop and schedule on success', async () => {
      const shop = createShop()
      mockGetShop.mockResolvedValueOnce({ shop })

      const store = useShopStore()
      await store.fetchShop()

      expect(store.shop).toEqual(shop)
      expect(store.schedule).toEqual(shop.schedule)
      expect(store.isLoading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('sets isLoading during fetch', async () => {
      let resolvePromise!: Function
      mockGetShop.mockReturnValueOnce(new Promise((r) => { resolvePromise = r }))

      const store = useShopStore()
      const promise = store.fetchShop()
      expect(store.isLoading).toBe(true)

      resolvePromise({ shop: createShop() })
      await promise
      expect(store.isLoading).toBe(false)
    })

    it('handles 404 as no shop (not error)', async () => {
      mockGetShop.mockRejectedValueOnce(createFetchError(404))

      const store = useShopStore()
      await store.fetchShop()

      expect(store.shop).toBeNull()
      expect(store.schedule).toEqual([])
      expect(store.error).toBeNull()
      expect(store.isLoading).toBe(false)
    })

    it('sets error on non-404 failure', async () => {
      mockGetShop.mockRejectedValueOnce(createFetchError(500))

      const store = useShopStore()
      await store.fetchShop()

      expect(store.error).toBe('Failed to load shop')
      expect(store.isLoading).toBe(false)
    })

    it('sets error on network failure', async () => {
      mockGetShop.mockRejectedValueOnce(new Error('Network error'))

      const store = useShopStore()
      await store.fetchShop()

      expect(store.error).toBe('Failed to load shop')
    })
  })

  describe('setShop', () => {
    it('updates shop and syncs schedule', () => {
      const store = useShopStore()
      const shop = createShop()
      store.setShop(shop)
      expect(store.shop).toEqual(shop)
      expect(store.schedule).toEqual(shop.schedule)
    })
  })

  describe('setSchedule', () => {
    it('updates schedule independently', () => {
      const store = useShopStore()
      const schedule = createDefaultSchedule()
      store.setSchedule(schedule)
      expect(store.schedule).toEqual(schedule)
    })
  })

  describe('clearShop', () => {
    it('resets all state', () => {
      const store = useShopStore()
      store.setShop(createShop())
      store.clearShop()

      expect(store.shop).toBeNull()
      expect(store.schedule).toEqual([])
      expect(store.isLoading).toBe(false)
      expect(store.error).toBeNull()
    })
  })
})