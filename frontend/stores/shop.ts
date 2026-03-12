import type { Shop, ScheduleEntry } from '~/types/shop'
import { FetchError } from 'ofetch'

export const useShopStore = defineStore('shop', () => {
  const shop = ref<Shop | null>(null)
  const schedule = ref<ScheduleEntry[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const hasShop = computed(() => shop.value !== null)
  const shopName = computed(() => shop.value?.name ?? '')

  async function fetchShop() {
    isLoading.value = true
    error.value = null

    try {
      const api = useShopApi()
      const response = await api.getShop()
      shop.value = response.shop
      schedule.value = response.shop.schedule
    } catch (err) {
      if (err instanceof FetchError && err.response?.status === 404) {
        shop.value = null
        schedule.value = []
      } else {
        error.value = 'Failed to load shop'
      }
    } finally {
      isLoading.value = false
    }
  }

  function setShop(newShop: Shop) {
    shop.value = newShop
    schedule.value = newShop.schedule
  }

  function setSchedule(newSchedule: ScheduleEntry[]) {
    schedule.value = newSchedule
  }

  function clearShop() {
    shop.value = null
    schedule.value = []
    isLoading.value = false
    error.value = null
  }

  return {
    shop,
    schedule,
    isLoading,
    error,
    hasShop,
    shopName,
    fetchShop,
    setShop,
    setSchedule,
    clearShop,
  }
})