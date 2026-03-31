import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import UpgradePrompt from '~/components/subscription/UpgradePrompt.vue'

const checkoutMock = vi.fn()

beforeEach(() => {
  checkoutMock.mockReset()
  vi.stubGlobal('useSubscriptionApi', () => ({
    checkout: checkoutMock,
    getSubscription: vi.fn(),
    upgradeRequest: vi.fn(),
  }))
})

function mountPrompt(props: { plan: 'free' | 'pro'; status?: 'active' | 'expired' | 'cancelled'; isExpiringSoon?: boolean }) {
  return mount(UpgradePrompt, {
    props: {
      status: 'active',
      isExpiringSoon: false,
      ...props,
    },
  })
}

describe('UpgradePrompt', () => {
  it('renders upgrade button for free plan', () => {
    const wrapper = mountPrompt({ plan: 'free' })
    expect(wrapper.text()).toContain('subscription.upgrade.title')
    expect(wrapper.text()).toContain('subscription.upgrade.cta')
  })

  it('does not render for active pro plan not expiring soon', () => {
    const wrapper = mountPrompt({ plan: 'pro', status: 'active', isExpiringSoon: false })
    expect(wrapper.text()).not.toContain('subscription.upgrade.title')
  })

  it('renders renew button for expiring pro plan', () => {
    const wrapper = mountPrompt({ plan: 'pro', status: 'active', isExpiringSoon: true })
    expect(wrapper.text()).toContain('subscription.upgrade.renewCta')
  })

  it('renders renew button for expired pro plan', () => {
    const wrapper = mountPrompt({ plan: 'pro', status: 'expired', isExpiringSoon: false })
    expect(wrapper.text()).toContain('subscription.upgrade.renewCta')
  })

  it('calls checkout when button clicked', async () => {
    checkoutMock.mockResolvedValue({ payUrl: 'https://pay.momo.vn/test' })
    const wrapper = mountPrompt({ plan: 'free' })
    await wrapper.find('button').trigger('click')
    expect(checkoutMock).toHaveBeenCalledOnce()
  })

  it('shows error toast when checkout fails', async () => {
    const toastErrorMock = vi.fn()
    vi.stubGlobal('useToast', () => ({ success: vi.fn(), error: toastErrorMock }))
    checkoutMock.mockRejectedValue(new Error('Network error'))
    const wrapper = mountPrompt({ plan: 'free' })
    await wrapper.find('button').trigger('click')
    await new Promise(r => setTimeout(r, 0))
    expect(toastErrorMock).toHaveBeenCalledWith('subscription.error.checkoutFailed')
  })
})