import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DayNav from '~/components/appointment/DayNav.vue'

function mountNav(date = '2026-03-15') {
  return mount(DayNav, { props: { date } })
}

// Compute expected shifted date (UTC-safe arithmetic matching component logic)
function shiftDate(dateStr: string, delta: number): string {
  const d = new Date(dateStr + 'T00:00:00')
  d.setDate(d.getDate() + delta)
  return d.toISOString().slice(0, 10)
}

describe('AppointmentDayNav', () => {
  describe('rendering', () => {
    it('renders prev and next navigation buttons', () => {
      const wrapper = mountNav()
      const buttons = wrapper.findAll('button')
      expect(buttons).toHaveLength(2)
    })

    it('renders a date input with current date value', () => {
      const wrapper = mountNav('2026-03-15')
      const input = wrapper.find('input[type="date"]')
      expect(input.exists()).toBe(true)
      expect((input.element as HTMLInputElement).value).toBe('2026-03-15')
    })
  })

  describe('prev/next navigation', () => {
    it('emits update:date with previous day when prev button clicked', async () => {
      const wrapper = mountNav('2026-03-15')
      await wrapper.findAll('button')[0].trigger('click')
      expect(wrapper.emitted('update:date')![0][0]).toBe(shiftDate('2026-03-15', -1))
    })

    it('emits update:date with next day when next button clicked', async () => {
      const wrapper = mountNav('2026-03-15')
      await wrapper.findAll('button')[1].trigger('click')
      expect(wrapper.emitted('update:date')![0][0]).toBe(shiftDate('2026-03-15', 1))
    })

    it('correctly crosses month boundaries', async () => {
      const wrapper = mountNav('2026-03-01')
      await wrapper.findAll('button')[0].trigger('click')
      expect(wrapper.emitted('update:date')![0][0]).toBe(shiftDate('2026-03-01', -1))
    })
  })

  describe('date input', () => {
    it('emits update:date with new value when date input changes', async () => {
      const wrapper = mountNav('2026-03-15')
      const input = wrapper.find('input[type="date"]')
      await input.setValue('2026-04-01')
      await input.trigger('input')
      expect(wrapper.emitted('update:date')![0][0]).toBe('2026-04-01')
    })

    it('does not emit when date input value is empty', async () => {
      const wrapper = mountNav('2026-03-15')
      const input = wrapper.find('input[type="date"]')
      await input.setValue('')
      await input.trigger('input')
      expect(wrapper.emitted('update:date')).toBeUndefined()
    })
  })
})
