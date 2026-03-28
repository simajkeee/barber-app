export const useOnboardingStore = defineStore('onboarding', () => {
  const shopStore = useShopStore()

  const serviceAdded = ref(false)
  const clientAdded = ref(false)
  const isFetching = ref(false)
  const isDismissed = ref(false)

  const shopCreated = computed(() => shopStore.hasShop)
  const scheduleConfigured = computed(() => shopStore.schedule.some(e => e.isOpen))
  const isComplete = computed(
    () => shopCreated.value && serviceAdded.value && scheduleConfigured.value && clientAdded.value,
  )
  const isOnboarding = computed(
    () => shopCreated.value && !isDismissed.value && !isComplete.value,
  )

  function init() {
    if (typeof window !== 'undefined') {
      isDismissed.value = localStorage.getItem('onboarding_dismissed') === '1'
    }
  }

  async function fetchChecklistData() {
    if (isFetching.value) return
    isFetching.value = true

    try {
      const clientApi = useClientApi()
      const response = await clientApi.listClients({ limit: 1 })
      clientAdded.value = response.data.length > 0
    } catch {
      clientAdded.value = false
    } finally {
      isFetching.value = false
    }
  }

  function setServiceAdded(value: boolean) {
    serviceAdded.value = value
  }

  function dismiss() {
    isDismissed.value = true
    if (typeof window !== 'undefined') {
      localStorage.setItem('onboarding_dismissed', '1')
    }
  }

  return {
    serviceAdded,
    clientAdded,
    isFetching,
    isDismissed,
    shopCreated,
    scheduleConfigured,
    isComplete,
    isOnboarding,
    init,
    fetchChecklistData,
    setServiceAdded,
    dismiss,
  }
})
