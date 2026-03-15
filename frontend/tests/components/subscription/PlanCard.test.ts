import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PlanCard from '~/components/subscription/PlanCard.vue'

describe('PlanCard', () => {
  function mountComponent(props = {}) {
    return mount(PlanCard, {
      props: {
        plan: 'free',
        status: 'active',
        startDate: '2026-03-01T00:00:00+07:00',
        endDate: null,
        ...props,
      },
    })
  }

  it('renders plan title', () => {
    const wrapper = mountComponent()
    expect(wrapper.text()).toContain('subscription.plan.title')
  })

  it('renders free plan badge', () => {
    const wrapper = mountComponent({ plan: 'free' })
    expect(wrapper.text()).toContain('subscription.plan.free')
  })

  it('renders pro plan badge', () => {
    const wrapper = mountComponent({ plan: 'pro' })
    expect(wrapper.text()).toContain('subscription.plan.pro')
  })

  it('renders status label', () => {
    const wrapper = mountComponent({ status: 'active' })
    expect(wrapper.text()).toContain('subscription.status.active')
  })

  it('renders start date', () => {
    const wrapper = mountComponent()
    expect(wrapper.text()).toContain('subscription.startDate')
  })

  it('renders end date when provided', () => {
    const wrapper = mountComponent({ endDate: '2026-04-14T00:00:00+07:00' })
    expect(wrapper.text()).toContain('subscription.endDate')
  })

  it('does not render end date when null', () => {
    const wrapper = mountComponent({ endDate: null })
    const texts = wrapper.findAll('span').map((s) => s.text())
    expect(texts).not.toContain('subscription.endDate')
  })

  it('renders days remaining for pro plan', () => {
    const wrapper = mountComponent({
      plan: 'pro',
      endDate: '2026-04-14T00:00:00+07:00',
      daysRemaining: 25,
    })
    expect(wrapper.text()).toContain('subscription.daysRemaining')
  })

  it('does not render days remaining for free plan', () => {
    const wrapper = mountComponent({ plan: 'free', daysRemaining: undefined })
    expect(wrapper.text()).not.toContain('subscription.daysRemaining')
  })

  it('applies pro badge styling for pro plan', () => {
    const wrapper = mountComponent({ plan: 'pro' })
    const badge = wrapper.find('.bg-primary-100')
    expect(badge.exists()).toBe(true)
  })

  it('applies green badge styling for free plan', () => {
    const wrapper = mountComponent({ plan: 'free' })
    const badge = wrapper.find('.bg-green-100')
    expect(badge.exists()).toBe(true)
  })
})
