import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import WorkingHours from '~/components/appointment/WorkingHours.vue'

function mountComponent(workingHours: { openTime: string; closeTime: string } | null) {
  return mount(WorkingHours, { props: { workingHours } })
}

describe('AppointmentWorkingHours', () => {
  it('shows working hours label when workingHours is provided', () => {
    const wrapper = mountComponent({ openTime: '09:00', closeTime: '18:00' })
    expect(wrapper.text()).toContain('appointments.workingHours.label')
  })

  it('shows closed text when workingHours is null', () => {
    const wrapper = mountComponent(null)
    expect(wrapper.text()).toContain('appointments.workingHours.closed')
  })
})
