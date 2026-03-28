import { describe, it, expect, vi, beforeEach } from 'vitest'
import { defineComponent } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createSubscriptionResponse } from '../../factories'
import { UiAlertStub } from '../../stubs'

const mockGetSubscription = vi.fn()

vi.stubGlobal('useSubscriptionApi', () => ({
  getSubscription: mockGetSubscription,
}))

const { default: SubscriptionPage } = await import('~/pages/dashboard/subscription/index.vue')

const PlanCardStub = {
  template: '<div class="plan-card" />',
  props: ['plan', 'status', 'startDate', 'endDate', 'daysRemaining'],
}

const UsageCardStub = {
  template: '<div class="usage-card" />',
  props: ['appointmentsThisMonth', 'appointmentLimit', 'limitReached'],
}

const UpgradePromptStub = {
  template: '<div class="upgrade-prompt" />',
  props: ['plan'],
}

const pageStubs = {
  DashboardPageHeader: { template: '<div />', props: ['title'] },
  SubscriptionPlanCard: PlanCardStub,
  SubscriptionUsageCard: UsageCardStub,
  SubscriptionUpgradePrompt: UpgradePromptStub,
  UiAlert: UiAlertStub,
}

describe('SubscriptionPage', () => {
  beforeEach(() => {
    mockGetSubscription.mockReset()
  })

  function mountPage() {
    return mount(
      defineComponent({
        components: { SubscriptionPage },
        template: '<Suspense><SubscriptionPage /></Suspense>',
      }),
      { global: { stubs: pageStubs } },
    )
  }

  describe('success state', () => {
    it('renders PlanCard with subscription data', async () => {
      const sub = createSubscriptionResponse({ plan: 'pro', status: 'active', daysRemaining: 20 })
      mockGetSubscription.mockResolvedValue(sub)
      const wrapper = mountPage()
      await flushPromises()
      const planCard = wrapper.findComponent(PlanCardStub)
      expect(planCard.exists()).toBe(true)
      expect(planCard.props('plan')).toBe('pro')
      expect(planCard.props('status')).toBe('active')
      expect(planCard.props('startDate')).toBe(sub.startDate)
      expect(planCard.props('endDate')).toBe(sub.endDate)
      expect(planCard.props('daysRemaining')).toBe(20)
    })

    it('renders UsageCard with usage data', async () => {
      const sub = createSubscriptionResponse({
        usage: { appointmentsThisMonth: 30, appointmentLimit: 50, limitReached: false },
      })
      mockGetSubscription.mockResolvedValue(sub)
      const wrapper = mountPage()
      await flushPromises()
      const usageCard = wrapper.findComponent(UsageCardStub)
      expect(usageCard.exists()).toBe(true)
      expect(usageCard.props('appointmentsThisMonth')).toBe(30)
      expect(usageCard.props('appointmentLimit')).toBe(50)
      expect(usageCard.props('limitReached')).toBe(false)
    })

    it('renders UpgradePrompt with plan', async () => {
      const sub = createSubscriptionResponse({ plan: 'free' })
      mockGetSubscription.mockResolvedValue(sub)
      const wrapper = mountPage()
      await flushPromises()
      const prompt = wrapper.findComponent(UpgradePromptStub)
      expect(prompt.exists()).toBe(true)
      expect(prompt.props('plan')).toBe('free')
    })

    it('does not show error alert on success', async () => {
      mockGetSubscription.mockResolvedValue(createSubscriptionResponse())
      const wrapper = mountPage()
      await flushPromises()
      expect(wrapper.findComponent(UiAlertStub).exists()).toBe(false)
    })
  })

  describe('error state', () => {
    it('shows error alert when fetch fails', async () => {
      mockGetSubscription.mockRejectedValue(new Error('Network error'))
      const wrapper = mountPage()
      await flushPromises()
      const alert = wrapper.findComponent(UiAlertStub)
      expect(alert.exists()).toBe(true)
      expect(alert.props('type')).toBe('error')
      expect(alert.props('message')).toBe('subscription.error.loadFailed')
    })

    it('does not show content cards when fetch fails', async () => {
      mockGetSubscription.mockRejectedValue(new Error('Network error'))
      const wrapper = mountPage()
      await flushPromises()
      expect(wrapper.find('.plan-card').exists()).toBe(false)
      expect(wrapper.find('.usage-card').exists()).toBe(false)
    })
  })

  describe('api call', () => {
    it('calls getSubscription on mount', async () => {
      mockGetSubscription.mockResolvedValue(createSubscriptionResponse())
      mountPage()
      await flushPromises()
      expect(mockGetSubscription).toHaveBeenCalledOnce()
    })
  })
})
