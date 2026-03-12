import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'
import { createUser } from '../factories'

// Mock useApi before importing the store
const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

// Must import after mocking
const { useAuthStore } = await import('~/stores/auth')

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockApiFetch.mockReset()
  })

  describe('initial state', () => {
    it('has null user and is not initialized', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()
      expect(store.isInitialized).toBe(false)
    })
  })

  describe('getters', () => {
    it('isAuthenticated returns false when no user', () => {
      const store = useAuthStore()
      expect(store.isAuthenticated).toBe(false)
    })

    it('isAuthenticated returns true when user exists', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      expect(store.isAuthenticated).toBe(true)
    })

    it('fullName returns empty string when no user', () => {
      const store = useAuthStore()
      expect(store.fullName).toBe('')
    })

    it('fullName returns first + last name', () => {
      const store = useAuthStore()
      store.setUser(createUser({ firstName: 'Jane', lastName: 'Smith' }))
      expect(store.fullName).toBe('Jane Smith')
    })

    it('userInitials returns empty string when no user', () => {
      const store = useAuthStore()
      expect(store.userInitials).toBe('')
    })

    it('userInitials returns uppercase initials', () => {
      const store = useAuthStore()
      store.setUser(createUser({ firstName: 'jane', lastName: 'smith' }))
      expect(store.userInitials).toBe('JS')
    })
  })

  describe('setUser', () => {
    it('sets user and marks as initialized', () => {
      const store = useAuthStore()
      const user = createUser()
      store.setUser(user)
      expect(store.user).toEqual(user)
      expect(store.isInitialized).toBe(true)
    })
  })

  describe('clear', () => {
    it('clears user and resets initialized', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      store.clear()
      expect(store.user).toBeNull()
      expect(store.isInitialized).toBe(false)
    })
  })

  describe('fetchUser', () => {
    it('fetches user from API and sets it', async () => {
      const store = useAuthStore()
      const user = createUser()
      mockApiFetch.mockResolvedValueOnce(user)

      await store.fetchUser()

      expect(mockApiFetch).toHaveBeenCalledWith('/auth/me')
      expect(store.user).toEqual(user)
      expect(store.isInitialized).toBe(true)
    })

    it('clears state and throws on API failure', async () => {
      const store = useAuthStore()
      store.setUser(createUser())
      mockApiFetch.mockRejectedValueOnce(new Error('Network error'))

      await expect(store.fetchUser()).rejects.toThrow('Failed to fetch user')
      expect(store.user).toBeNull()
      expect(store.isInitialized).toBe(false)
    })
  })
})