import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DetailCard from '~/components/appointment/DetailCard.vue'
import { createAppointment } from '../../factories'

const childStubs = {
  AppointmentStatusBadge: { template: '<span class="status-badge">{{ status }}</span>', props: ['status'] },
  AppointmentTimeBadge: { template: '<span class="time-badge">time</span>', props: ['startTime', 'endTime', 'showDate'] },
  // Renders an <a> tag when linkable=true so we can assert that
  // Props must declare Boolean type so Vue casts `linkable` (empty-string attribute) to `true`
  AppointmentClientInfo: {
    template: '<div><a v-if="linkable" href="#">{{ client.firstName }}</a><span v-else>{{ client.firstName }}</span></div>',
    props: { client: Object, linkable: Boolean },
  },
}

function mountCard(overrides: Record<string, any> = {}) {
  return mount(DetailCard, {
    props: { appointment: createAppointment(overrides) },
    global: { stubs: childStubs },
  })
}

describe('AppointmentDetailCard', () => {
  it('shows status via StatusBadge', () => {
    const wrapper = mountCard({ status: 'completed' })
    expect(wrapper.find('.status-badge').text()).toBe('completed')
  })

  it('shows service name', () => {
    const wrapper = mountCard({ service: { id: 's-1', name: 'Beard Trim', durationMinutes: 20, price: 120000 } })
    expect(wrapper.text()).toContain('Beard Trim')
  })

  it('renders ClientInfo with linkable=true so name appears as a link', () => {
    const wrapper = mountCard({ client: { id: 'c-1', firstName: 'Lan', lastName: 'Vu', phone: '123' } })
    // ClientInfo stub renders an <a> tag when linkable=true
    expect(wrapper.find('a').exists()).toBe(true)
    expect(wrapper.text()).toContain('Lan')
  })

  it('shows notes when present', () => {
    const wrapper = mountCard({ notes: 'Allergic to product X' })
    expect(wrapper.text()).toContain('Allergic to product X')
  })

  it('shows no-notes i18n key when notes is null', () => {
    const wrapper = mountCard({ notes: null })
    expect(wrapper.text()).toContain('appointments.detail.noNotes')
  })

  it('shows formatted service duration and price', () => {
    const wrapper = mountCard({ service: { id: 's-1', name: 'Cut', durationMinutes: 30, price: 150000 } })
    // formatDuration and formatPrice are stubbed in setup.ts
    expect(wrapper.text()).toContain('30 min')
    expect(wrapper.text()).toContain('150000 ₫')
  })
})
