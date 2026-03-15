import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TimeBadge from '~/components/appointment/TimeBadge.vue'

// 09:00 – 09:30 Ho Chi Minh City (UTC+7)
const START = '2026-03-15T02:00:00.000Z'
const END = '2026-03-15T02:30:00.000Z'

function mountBadge(props: Record<string, any> = {}) {
  return mount(TimeBadge, {
    props: { startTime: START, endTime: END, ...props },
  })
}

describe('AppointmentTimeBadge', () => {
  describe('time display', () => {
    it('renders a time range with separator', () => {
      const wrapper = mountBadge()
      expect(wrapper.text()).toContain('–')
    })

    it('renders formatted times containing colon', () => {
      const wrapper = mountBadge()
      // Both start and end time should contain hour:minute
      const text = wrapper.text()
      expect(text.match(/\d{1,2}:\d{2}/g)?.length).toBeGreaterThanOrEqual(2)
    })
  })

  describe('date prefix', () => {
    it('does not show date prefix by default', () => {
      const wrapper = mountBadge()
      // The middle dot separator only appears when showDate is true
      expect(wrapper.text()).not.toContain('·')
    })

    it('shows date prefix and middle dot separator when showDate is true', () => {
      const wrapper = mountBadge({ showDate: true })
      expect(wrapper.text()).toContain('·')
    })
  })
})
