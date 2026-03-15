import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UpgradePrompt from '~/components/subscription/UpgradePrompt.vue'

describe('UpgradePrompt', () => {
  it('renders upgrade prompt for free plan', () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'free' },
    })
    expect(wrapper.text()).toContain('subscription.upgrade.title')
    expect(wrapper.text()).toContain('subscription.upgrade.description')
    expect(wrapper.text()).toContain('subscription.upgrade.contact')
  })

  it('does not render for pro plan', () => {
    const wrapper = mount(UpgradePrompt, {
      props: { plan: 'pro' },
    })
    expect(wrapper.text()).not.toContain('subscription.upgrade.title')
  })
})
