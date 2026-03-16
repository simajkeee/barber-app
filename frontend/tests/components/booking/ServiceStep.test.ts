import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceStep from '~/components/booking/ServiceStep.vue'
import type { PublicService } from '~/types/booking'

const services: PublicService[] = [
  { id: 'svc-1', name: 'Haircut', duration: 30, price: 100000 },
  { id: 'svc-2', name: 'Beard Trim', duration: 15, price: 50000 },
]

describe('ServiceStep', () => {
  it('renders all services', () => {
    const wrapper = mount(ServiceStep, {
      props: { services, selectedId: null },
    })

    const buttons = wrapper.findAll('button')
    expect(buttons).toHaveLength(2)
    expect(buttons[0].text()).toContain('Haircut')
    expect(buttons[1].text()).toContain('Beard Trim')
  })

  it('highlights selected service', () => {
    const wrapper = mount(ServiceStep, {
      props: { services, selectedId: 'svc-1' },
    })

    const buttons = wrapper.findAll('button')
    expect(buttons[0].classes()).toContain('border-primary-500')
    expect(buttons[1].classes()).not.toContain('border-primary-500')
  })

  it('emits select event on click', async () => {
    const wrapper = mount(ServiceStep, {
      props: { services, selectedId: null },
    })

    await wrapper.findAll('button')[1].trigger('click')

    expect(wrapper.emitted('select')).toHaveLength(1)
    expect(wrapper.emitted('select')![0]).toEqual([services[1]])
  })

  it('displays price and duration', () => {
    const wrapper = mount(ServiceStep, {
      props: { services, selectedId: null },
    })

    const text = wrapper.text()
    expect(text).toContain('100000')
    expect(text).toContain('30 min')
  })
})
