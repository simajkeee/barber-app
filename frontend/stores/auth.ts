import type { User } from '~/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isInitialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  const fullName = computed(() => {
    if (!user.value) return ''
    return `${user.value.firstName} ${user.value.lastName}`.trim() || '?'
  })

  const userInitials = computed(() => {
    if (!user.value) return ''
    return (user.value.firstName.charAt(0) + user.value.lastName.charAt(0)).toUpperCase() || '?'
  })

  function setUser(newUser: User) {
    user.value = newUser
    isInitialized.value = true
  }

  function clear() {
    user.value = null
    isInitialized.value = false
  }

  async function fetchUser() {
    const api = useApi()
    try {
      const data = await api<User>('/auth/me')
      setUser(data)
    } catch {
      clear()
      throw new Error('Failed to fetch user')
    }
  }

  return {
    user,
    isInitialized,
    isAuthenticated,
    fullName,
    userInitials,
    setUser,
    clear,
    fetchUser,
  }
})