import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import DailyCard from '~/components/appointment/DailyCard.vue'
import { createAppointment } from '../../factories'
import { appointmentCardChildStubs } from '../../stubs'

function mountCard(overrides: Record<string, any> = {}) {
  return mount(DailyCard, {
    props: { appointment: createAppointment(), ...overrides },
    global: { stubs: appointmentCardChildStubs },
  })
}

describe('AppointmentDailyCard', () => {
  describe('rendering', () => {
    it('shows client first name via ClientInfo', () => {
      const wrapper = mountCard({ appointment: createAppointment({ client: { id: 'c-1', firstName: 'Bao', lastName: 'Le', phone: '123' } }) })
      expect(wrapper.text()).toContain('Bao')
    })

    it('shows service name via ServiceInfo', () => {
      const wrapper = mountCard({ appointment: createAppointment({ service: { id: 's-1', name: 'Shave', durationMinutes: 20, price: 100000 } }) })
      expect(wrapper.text()).toContain('Shave')
    })

    it('shows notes when present', () => {
      const wrapper = mountCard({ appointment: createAppointment({ notes: 'VIP client' }) })
      expect(wrapper.text()).toContain('VIP client')
    })

    it('hides notes section when notes is null', () => {
      const wrapper = mountCard({ appointment: createAppointment({ notes: null }) })
      expect(wrapper.find('p.italic').exists()).toBe(false)
    })
  })

  describe('event forwarding', () => {
    let wrapper: VueWrapper

    beforeEach(() => {
      wrapper = mountCard({ appointment: createAppointment({ id: 'appt-42' }) })
    })

    it('emits view with appointment id when view button clicked', async () => {
      await wrapper.find('.btn-view').trigger('click')
      expect(wrapper.emitted('view')![0][0]).toBe('appt-42')
    })

    it('emits complete with appointment id', async () => {
      await wrapper.find('.btn-complete').trigger('click')
      expect(wrapper.emitted('complete')![0][0]).toBe('appt-42')
    })

    it('emits noShow with appointment id', async () => {
      await wrapper.find('.btn-no-show').trigger('click')
      expect(wrapper.emitted('noShow')![0][0]).toBe('appt-42')
    })

    it('emits cancel with appointment id', async () => {
      await wrapper.find('.btn-cancel').trigger('click')
      expect(wrapper.emitted('cancel')![0][0]).toBe('appt-42')
    })
  })
})
