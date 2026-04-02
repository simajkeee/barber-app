import type { SubscriptionResponse } from '~/types/subscription'

export function useSubscriptionApi() {
  const api = useApi()

  async function getSubscription(): Promise<SubscriptionResponse> {
    return api<SubscriptionResponse>('/subscription')
  }

  async function upgradeRequest(data: {
    name: string
    email: string
    phone?: string
    message?: string
  }): Promise<{ id: string; createdAt: string }> {
    return api<{ id: string; createdAt: string }>('/subscription/upgrade-request', {
      method: 'POST',
      body: data,
    })
  }

  async function checkout(): Promise<{ payUrl: string }> {
    return api<{ payUrl: string }>('/subscription/checkout', { method: 'POST' })
  }

  return { getSubscription, upgradeRequest, checkout }
}
