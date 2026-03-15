import type { SubscriptionResponse } from '~/types/subscription'

export function useSubscriptionApi() {
  const api = useApi()

  async function getSubscription(): Promise<SubscriptionResponse> {
    return api<SubscriptionResponse>('/subscription/')
  }

  return { getSubscription }
}
