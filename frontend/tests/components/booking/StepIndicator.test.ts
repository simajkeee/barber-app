import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StepIndicator from '~/components/booking/StepIndicator.vue'

const labels = ['Service', 'Date & Time', 'Details', 'Confirm']

describe('StepIndicator', () => {
  it('renders all step labels', () => {
    const wrapper = mount(StepIndicator, {
      props: { currentStep: 1, totalSteps: 4, labels },
    })

    expect(wrapper.text()).toContain('Service')
    expect(wrapper.text()).toContain('Confirm')
  })

  it('highlights completed steps', () => {
    const wrapper = mount(StepIndicator, {
      props: { currentStep: 3, totalSteps: 4, labels },
    })

    const circles = wrapper.findAll('.rounded-full')
    expect(circles[0].classes()).toContain('bg-primary-600')
    expect(circles[1].classes()).toContain('bg-primary-600')
    expect(circles[2].classes()).toContain('bg-primary-600')
    expect(circles[3].classes()).toContain('bg-gray-200')
  })

  it('shows step numbers', () => {
    const wrapper = mount(StepIndicator, {
      props: { currentStep: 1, totalSteps: 4, labels },
    })

    const circles = wrapper.findAll('.rounded-full')
    expect(circles[0].text()).toBe('1')
    expect(circles[3].text()).toBe('4')
  })
})
