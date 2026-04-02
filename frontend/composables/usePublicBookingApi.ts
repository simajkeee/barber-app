import type {
  BookingRequest,
  BookingResponse,
  PublicAvailableSlotsResponse,
  PublicShopInfo,
} from '~/types/booking'

export function usePublicBookingApi() {
  const config = useRuntimeConfig()
  const apiBase = import.meta.server ? config.apiBase : config.public.apiBase

  async function getShopInfo(slug: string): Promise<PublicShopInfo> {
    return $fetch<PublicShopInfo>(`${apiBase}/public/shops/${slug}`)
  }

  async function getAvailableSlots(
    slug: string,
    date: string,
    serviceId: string,
  ): Promise<PublicAvailableSlotsResponse> {
    return $fetch<PublicAvailableSlotsResponse>(
      `${apiBase}/public/shops/${slug}/available-slots`,
      { query: { date, serviceId } },
    )
  }

  async function createBooking(slug: string, data: BookingRequest): Promise<BookingResponse> {
    return $fetch<BookingResponse>(
      `${apiBase}/public/shops/${slug}/book`,
      { method: 'POST', body: data },
    )
  }

  return { getShopInfo, getAvailableSlots, createBooking }
}
