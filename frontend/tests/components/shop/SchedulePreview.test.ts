import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SchedulePreview from '~/components/shop/SchedulePreview.vue'
import { createScheduleEntry } from '../../factories'

describe('SchedulePreview', () => {
  it('renders title', () => {
    const wrapper = mount(SchedulePreview, {
      props: { schedule: [] },
    })
    expect(wrapper.find('h2').text()).toContain('shop.schedule.title')
  })

  it('renders a row for each schedule entry', () => {
    const schedule = [
      createScheduleEntry({ dayOfWeek: 'monday' }),
      createScheduleEntry({ dayOfWeek: 'tuesday' }),
    ]
    const wrapper = mount(SchedulePreview, { props: { schedule } })
    const rows = wrapper.findAll('.divide-y > div')
    expect(rows).toHaveLength(2)
  })

  it('shows day name for each entry', () => {
    const schedule = [createScheduleEntry({ dayOfWeek: 'wednesday' })]
    const wrapper = mount(SchedulePreview, { props: { schedule } })
    expect(wrapper.text()).toContain('shop.schedule.days.wednesday')
  })

  it('shows open/close times for open days', () => {
    const schedule = [
      createScheduleEntry({ dayOfWeek: 'monday', isOpen: true, openTime: '08:00', closeTime: '17:00' }),
    ]
    const wrapper = mount(SchedulePreview, { props: { schedule } })
    expect(wrapper.text()).toContain('08:00')
    expect(wrapper.text()).toContain('17:00')
  })

  it('shows closed text for closed days', () => {
    const schedule = [
      createScheduleEntry({ dayOfWeek: 'sunday', isOpen: false, openTime: null, closeTime: null }),
    ]
    const wrapper = mount(SchedulePreview, { props: { schedule } })
    expect(wrapper.text()).toContain('shop.schedule.closed')
  })
})