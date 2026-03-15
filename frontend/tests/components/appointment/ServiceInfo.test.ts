import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceInfo from '~/components/appointment/ServiceInfo.vue'
import { createAppointmentService } from '../../factories'

function mountInfo(overrides: Record<string, any> = {}) {
  return mount(ServiceInfo, {
    props: { service: createAppointmentService(overrides) },
  })
}

describe('AppointmentServiceInfo', () => {
  it('displays service name', () => {
    const wrapper = mountInfo({ name: 'Beard Trim' })
    expect(wrapper.text()).toContain('Beard Trim')
  })

  it('displays formatted duration via useFormatters stub', () => {
    const wrapper = mountInfo({ durationMinutes: 45 })
    // setup.ts stubs formatDuration as `${min} min`
    expect(wrapper.text()).toContain('45 min')
  })

  it('displays formatted price via useFormatters stub', () => {
    const wrapper = mountInfo({ price: 200000 })
    // setup.ts stubs formatPrice as `${price} ₫`
    expect(wrapper.text()).toContain('200000 ₫')
  })
})
