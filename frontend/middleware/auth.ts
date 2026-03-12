export default defineNuxtRouteMiddleware(async () => {
  const token = useCookie('access_token')
  const localePath = useLocalePath()

  if (!token.value) {
    return navigateTo(localePath('/login'))
  }

  const store = useAuthStore()
  if (!store.isInitialized) {
    try {
      await store.fetchUser()
    } catch {
      return navigateTo(localePath('/login'))
    }
  }
})