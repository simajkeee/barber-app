import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import UpgradePrompt from '~/components/subscription/UpgradePrompt.vue'

const upgradeRequestMock = vi.fn()

beforeEach(() => {
  upgradeRequestMock.mockReset()
  vi.stubGlobal('useSubscriptionApi', () => ({
    upgradeRequest: upgradeRequestMock,
    getSubscription: vi.fn(),
  }))
  vi.stubGlobal('useAuthStore', () => ({
    user: { email: 'test@example.com' },
  }))
})

describe('UpgradePrompt', () => {
  it('renders upgrade prompt for free plan', () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'free' },
    })
    expect(wrapper.text()).toContain('subscription.upgrade.title')
    expect(wrapper.text()).toContain('subscription.upgrade.description')
    expect(wrapper.text()).toContain('subscription.upgrade.requestButton')
  })

  it('does not render for pro plan', () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'pro' },
    })
    expect(wrapper.text()).not.toContain('subscription.upgrade.title')
  })

  it('shows inline form when request button clicked', async () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'free' },
    })
    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('subscription.upgrade.formTitle')
  })

  it('hides form when cancel clicked', async () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'free' },
    })
    await wrapper.find('button').trigger('click')
    const cancelBtn = wrapper.findAll('button').find(b => b.text().includes('common.cancel'))!
    await cancelBtn.trigger('click')
    expect(wrapper.text()).not.toContain('subscription.upgrade.formTitle')
  })
})
