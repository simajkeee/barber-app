export type SubscriptionPlan = 'free' | 'pro'
export type SubscriptionStatus = 'active' | 'expired' | 'cancelled'

export interface SubscriptionUsage {
  appointmentsThisMonth: number
  appointmentLimit: number | null
  limitReached: boolean
}

export interface SubscriptionTrial {
  isInTrial: boolean
  trialEndsAt: string | null
  trialDaysRemaining: number | null
}

export interface SubscriptionResponse {
  id: string
  plan: SubscriptionPlan
  status: SubscriptionStatus
  startDate: string
  endDate: string | null
  daysRemaining: number | null
  isExpiringSoon: boolean
  trial: SubscriptionTrial
  usage: SubscriptionUsage
}
