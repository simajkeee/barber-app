import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useOnboardingStore } from '~/stores/onboarding'
import { createClient, createClientListResponse } from '../factories'

const mockListClients = vi.fn()

vi.stubGlobal('useClientApi', () => ({
  listClients: mockListClients,
}))

const mockShopStore = {
  hasShop: false,
  schedule: [] as { isOpen: boolean }[],
}

vi.stubGlobal('useShopStore', () => mockShopStore)

describe('useOnboardingStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockListClients.mockReset()
    mockShopStore.hasShop = false
    mockShopStore.schedule = []
    localStorage.clear()
  })

  describe('init', () => {
    it('reads isDismissed from localStorage', () => {
      localStorage.setItem('onboarding_dismissed', '1')
      const store = useOnboardingStore()

      store.init()

      expect(store.isDismissed).toBe(true)
    })

    it('sets isDismissed to false when localStorage is empty', () => {
      const store = useOnboardingStore()

      store.init()

      expect(store.isDismissed).toBe(false)
    })
  })

  describe('fetchChecklistData', () => {
    it('sets clientAdded true when clients exist', async () => {
      mockListClients.mockResolvedValueOnce(createClientListResponse([createClient()]))
      const store = useOnboardingStore()

      await store.fetchChecklistData()

      expect(store.clientAdded).toBe(true)
      expect(mockListClients).toHaveBeenCalledWith({ limit: 1 })
    })

    it('sets clientAdded false when no clients', async () => {
      mockListClients.mockResolvedValueOnce(createClientListResponse([]))
      const store = useOnboardingStore()

      await store.fetchChecklistData()

      expect(store.clientAdded).toBe(false)
    })

    it('sets clientAdded false on API failure', async () => {
      mockListClients.mockRejectedValueOnce(new Error('Network error'))
      const store = useOnboardingStore()

      await store.fetchChecklistData()

      expect(store.clientAdded).toBe(false)
    })

    it('prevents concurrent fetches', async () => {
      let resolveFirst: Function
      mockListClients.mockReturnValueOnce(new Promise(r => { resolveFirst = r }))
      const store = useOnboardingStore()

      const first = store.fetchChecklistData()
      const second = store.fetchChecklistData()

      resolveFirst!(createClientListResponse([createClient()]))
      await first
      await second

      expect(mockListClients).toHaveBeenCalledTimes(1)
    })
  })

  describe('setServiceAdded', () => {
    it('sets serviceAdded flag', () => {
      const store = useOnboardingStore()

      store.setServiceAdded(true)
      expect(store.serviceAdded).toBe(true)

      store.setServiceAdded(false)
      expect(store.serviceAdded).toBe(false)
    })
  })

  describe('dismiss', () => {
    it('sets isDismissed and writes to localStorage', () => {
      const store = useOnboardingStore()

      store.dismiss()

      expect(store.isDismissed).toBe(true)
      expect(localStorage.getItem('onboarding_dismissed')).toBe('1')
    })
  })

  describe('computed: isComplete', () => {
    it('is true when all four criteria are met', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()
      store.setServiceAdded(true)
      store.clientAdded = true

      expect(store.isComplete).toBe(true)
    })

    it('is false when shop not created', () => {
      mockShopStore.hasShop = false
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()
      store.setServiceAdded(true)
      store.clientAdded = true

      expect(store.isComplete).toBe(false)
    })

    it('is false when no services', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()
      store.clientAdded = true

      expect(store.isComplete).toBe(false)
    })

    it('is false when no schedule', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: false }]
      const store = useOnboardingStore()
      store.setServiceAdded(true)
      store.clientAdded = true

      expect(store.isComplete).toBe(false)
    })

    it('is false when no clients', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()
      store.setServiceAdded(true)

      expect(store.isComplete).toBe(false)
    })
  })

  describe('computed: isOnboarding', () => {
    it('is true when shop exists, not dismissed, not complete', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()

      expect(store.isOnboarding).toBe(true)
    })

    it('is false when isDismissed', () => {
      mockShopStore.hasShop = true
      const store = useOnboardingStore()
      store.dismiss()

      expect(store.isOnboarding).toBe(false)
    })

    it('is false when isComplete', () => {
      mockShopStore.hasShop = true
      mockShopStore.schedule = [{ isOpen: true }]
      const store = useOnboardingStore()
      store.setServiceAdded(true)
      store.clientAdded = true

      expect(store.isOnboarding).toBe(false)
    })

    it('is false when no shop', () => {
      mockShopStore.hasShop = false
      const store = useOnboardingStore()

      expect(store.isOnboarding).toBe(false)
    })
  })

  describe('computed: shopCreated', () => {
    it('is false when shop does not exist', () => {
      mockShopStore.hasShop = false
      const store = useOnboardingStore()
      expect(store.shopCreated).toBe(false)
    })

    it('is true when shop exists', () => {
      mockShopStore.hasShop = true
      const store = useOnboardingStore()
      expect(store.shopCreated).toBe(true)
    })
  })

  describe('computed: scheduleConfigured', () => {
    it('is true when at least one day is open', () => {
      mockShopStore.schedule = [{ isOpen: false }, { isOpen: true }]
      const store = useOnboardingStore()

      expect(store.scheduleConfigured).toBe(true)
    })

    it('is false when no days are open', () => {
      mockShopStore.schedule = [{ isOpen: false }]
      const store = useOnboardingStore()

      expect(store.scheduleConfigured).toBe(false)
    })
  })
})
