import type {
  BookingRequest,
  BookingResponse,
  PublicAvailableSlotsResponse,
  PublicShopInfo,
} from '~/types/booking'

export function usePublicBookingApi() {
  const config = useRuntimeConfig()

  async function getShopInfo(slug: string): Promise<PublicShopInfo> {
    return $fetch<PublicShopInfo>(`${config.public.apiBase}/public/shops/${slug}`)
  }

  async function getAvailableSlots(
    slug: string,
    date: string,
    serviceId: string,
  ): Promise<PublicAvailableSlotsResponse> {
    return $fetch<PublicAvailableSlotsResponse>(
      `${config.public.apiBase}/public/shops/${slug}/available-slots`,
      { query: { date, serviceId } },
    )
  }

  async function createBooking(slug: string, data: BookingRequest): Promise<BookingResponse> {
    return $fetch<BookingResponse>(
      `${config.public.apiBase}/public/shops/${slug}/book`,
      { method: 'POST', body: data },
    )
  }

  return { getShopInfo, getAvailableSlots, createBooking }
}
