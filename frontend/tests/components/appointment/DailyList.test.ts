import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DailyList from '~/components/appointment/DailyList.vue'
import { createAppointment } from '../../factories'

const defaultStubs = {
  AppointmentDailyCard: {
    template: `<div class="daily-card">
      <button class="btn-view" @click="$emit('view', appointment.id)">view</button>
      <button class="btn-complete" @click="$emit('complete', appointment.id)">complete</button>
      <button class="btn-no-show" @click="$emit('noShow', appointment.id)">noShow</button>
      <button class="btn-cancel" @click="$emit('cancel', appointment.id)">cancel</button>
    </div>`,
    props: ['appointment', 'loading'],
    emits: ['view', 'complete', 'noShow', 'cancel'],
  },
  UiEmptyState: {
    template: '<div class="empty-state"><h3>{{ title }}</h3></div>',
    props: ['title', 'description'],
  },
}

function mountList(props: Record<string, any> = {}) {
  return mount(DailyList, {
    props: {
      appointments: [],
      isLoading: false,
      workingHours: { openTime: '09:00', closeTime: '18:00' },
      ...props,
    },
    global: { stubs: defaultStubs },
  })
}

describe('AppointmentDailyList', () => {
  describe('loading state', () => {
    it('shows spinner when isLoading is true', () => {
      const wrapper = mountList({ isLoading: true })
      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })

    it('hides content when loading', () => {
      const wrapper = mountList({ isLoading: true, appointments: [createAppointment()] })
      expect(wrapper.findAll('.daily-card')).toHaveLength(0)
    })
  })

  describe('closed state', () => {
    it('shows closed empty state when workingHours is null', () => {
      const wrapper = mountList({ workingHours: null })
      expect(wrapper.find('.empty-state h3').text()).toBe('appointments.daily.closed')
    })
  })

  describe('empty state', () => {
    it('shows empty day state when no appointments but shop is open', () => {
      const wrapper = mountList({ appointments: [] })
      expect(wrapper.find('.empty-state h3').text()).toBe('appointments.daily.empty')
    })
  })

  describe('appointment list', () => {
    it('renders a DailyCard for each appointment', () => {
      const appointments = [createAppointment({ id: '1' }), createAppointment({ id: '2' })]
      const wrapper = mountList({ appointments })
      expect(wrapper.findAll('.daily-card')).toHaveLength(2)
    })

    it('forwards view event from DailyCard', async () => {
      const wrapper = mountList({ appointments: [createAppointment({ id: 'appt-7' })] })
      await wrapper.find('.btn-view').trigger('click')
      expect(wrapper.emitted('view')![0][0]).toBe('appt-7')
    })

    it('forwards complete event from DailyCard', async () => {
      const wrapper = mountList({ appointments: [createAppointment({ id: 'appt-7' })] })
      await wrapper.find('.btn-complete').trigger('click')
      expect(wrapper.emitted('complete')![0][0]).toBe('appt-7')
    })
  })
})
