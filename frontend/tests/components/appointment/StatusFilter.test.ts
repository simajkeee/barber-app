import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusFilter from '~/components/appointment/StatusFilter.vue'
import type { AppointmentStatus } from '~/types/appointment'

function mountFilter(modelValue: AppointmentStatus[] = []) {
  return mount(StatusFilter, { props: { modelValue } })
}

describe('AppointmentStatusFilter', () => {
  it('renders a button for each of the 4 statuses plus an All button', () => {
    const wrapper = mountFilter()
    expect(wrapper.findAll('button')).toHaveLength(5)
  })

  it('shows status i18n keys as button labels', () => {
    const wrapper = mountFilter()
    const text = wrapper.text()
    expect(text).toContain('appointments.status.scheduled')
    expect(text).toContain('appointments.status.completed')
    expect(text).toContain('appointments.status.cancelled')
    expect(text).toContain('appointments.status.no_show')
  })

  describe('toggle behaviour', () => {
    it('adds status to selection when clicking an unselected status', async () => {
      const wrapper = mountFilter([])
      await wrapper.findAll('button')[0].trigger('click') // scheduled
      expect(wrapper.emitted('update:modelValue')![0][0]).toEqual(['scheduled'])
    })

    it('removes status from selection when clicking an already-selected status', async () => {
      const wrapper = mountFilter(['scheduled', 'completed'])
      // Click the scheduled button (first button)
      await wrapper.findAll('button')[0].trigger('click')
      expect(wrapper.emitted('update:modelValue')![0][0]).toEqual(['completed'])
    })

    it('can select multiple statuses independently', async () => {
      const wrapper = mountFilter(['scheduled'])
      await wrapper.findAll('button')[1].trigger('click') // completed
      expect(wrapper.emitted('update:modelValue')![0][0]).toEqual(['scheduled', 'completed'])
    })
  })
})
