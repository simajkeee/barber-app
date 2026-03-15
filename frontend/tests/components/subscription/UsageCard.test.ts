import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UsageCard from '~/components/subscription/UsageCard.vue'
import { UiAlertStub } from '../../stubs'

describe('UsageCard', () => {
  function mountComponent(props = {}) {
    return mount(UsageCard, {
      props: {
        appointmentsThisMonth: 18,
        appointmentLimit: 50,
        limitReached: false,
        ...props,
      },
      global: {
        stubs: {
          UiAlert: UiAlertStub,
        },
      },
    })
  }

  it('renders usage title', () => {
    const wrapper = mountComponent()
    expect(wrapper.text()).toContain('subscription.usage.title')
  })

  it('renders appointment count', () => {
    const wrapper = mountComponent({ appointmentsThisMonth: 25 })
    expect(wrapper.text()).toContain('25')
  })

  it('renders limit for free plan', () => {
    const wrapper = mountComponent({ appointmentLimit: 50 })
    expect(wrapper.text()).toContain('50')
  })

  it('renders unlimited for pro plan', () => {
    const wrapper = mountComponent({ appointmentLimit: null })
    expect(wrapper.text()).toContain('subscription.usage.unlimited')
  })

  it('shows progress bar for free plan', () => {
    const wrapper = mountComponent({ appointmentLimit: 50 })
    const progressbar = wrapper.find('[role="progressbar"]')
    expect(progressbar.exists()).toBe(true)
  })

  it('does not show progress bar for pro plan', () => {
    const wrapper = mountComponent({ appointmentLimit: null })
    const progressbar = wrapper.find('[role="progressbar"]')
    expect(progressbar.exists()).toBe(false)
  })

  it('shows warning when limit reached', () => {
    const wrapper = mountComponent({ limitReached: true })
    expect(wrapper.text()).toContain('subscription.usage.limitReached')
  })

  it('does not show warning when limit not reached', () => {
    const wrapper = mountComponent({ limitReached: false })
    expect(wrapper.text()).not.toContain('subscription.usage.limitReached')
  })

  it('sets correct aria attributes on progress bar', () => {
    const wrapper = mountComponent({
      appointmentsThisMonth: 30,
      appointmentLimit: 50,
    })
    const progressbar = wrapper.find('[role="progressbar"]')
    expect(progressbar.attributes('aria-valuenow')).toBe('30')
    expect(progressbar.attributes('aria-valuemin')).toBe('0')
    expect(progressbar.attributes('aria-valuemax')).toBe('50')
  })

  it('shows remaining count', () => {
    const wrapper = mountComponent({
      appointmentsThisMonth: 30,
      appointmentLimit: 50,
    })
    expect(wrapper.text()).toContain('subscription.usage.remaining')
  })
})
