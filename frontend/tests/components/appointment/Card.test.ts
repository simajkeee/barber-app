import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import Card from '~/components/appointment/Card.vue'
import { createAppointment } from '../../factories'
import { appointmentCardChildStubs } from '../../stubs'

function mountCard(overrides: Record<string, any> = {}) {
  return mount(Card, {
    props: { appointment: createAppointment(), ...overrides },
    global: { stubs: appointmentCardChildStubs },
  })
}

describe('AppointmentCard (list view)', () => {
  it('shows client name', () => {
    const wrapper = mountCard({ appointment: createAppointment({ client: { id: 'c-1', firstName: 'Lan', lastName: 'Vu', phone: '123' } }) })
    expect(wrapper.text()).toContain('Lan')
  })

  it('shows notes when present', () => {
    const wrapper = mountCard({ appointment: createAppointment({ notes: 'Regular client' }) })
    expect(wrapper.text()).toContain('Regular client')
  })

  it('hides notes when null', () => {
    const wrapper = mountCard({ appointment: createAppointment({ notes: null }) })
    expect(wrapper.find('p.italic').exists()).toBe(false)
  })

  describe('event forwarding', () => {
    let wrapper: VueWrapper

    beforeEach(() => {
      wrapper = mountCard({ appointment: createAppointment({ id: 'appt-99' }) })
    })

    it('emits view with appointment id', async () => {
      await wrapper.find('.btn-view').trigger('click')
      expect(wrapper.emitted('view')![0][0]).toBe('appt-99')
    })

    it('emits complete with appointment id', async () => {
      await wrapper.find('.btn-complete').trigger('click')
      expect(wrapper.emitted('complete')![0][0]).toBe('appt-99')
    })

    it('emits noShow with appointment id', async () => {
      await wrapper.find('.btn-no-show').trigger('click')
      expect(wrapper.emitted('noShow')![0][0]).toBe('appt-99')
    })

    it('emits cancel with appointment id', async () => {
      await wrapper.find('.btn-cancel').trigger('click')
      expect(wrapper.emitted('cancel')![0][0]).toBe('appt-99')
    })
  })
})
