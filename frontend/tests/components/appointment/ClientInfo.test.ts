import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ClientInfo from '~/components/appointment/ClientInfo.vue'
import { createAppointmentClient } from '../../factories'

function mountInfo(props: Record<string, any> = {}) {
  return mount(ClientInfo, {
    props: { client: createAppointmentClient(), ...props },
  })
}

describe('AppointmentClientInfo', () => {
  describe('rendering', () => {
    it('displays full name', () => {
      const wrapper = mountInfo({ client: createAppointmentClient({ firstName: 'Tran', lastName: 'Minh' }) })
      expect(wrapper.text()).toContain('Tran')
      expect(wrapper.text()).toContain('Minh')
    })

    it('displays phone number', () => {
      const wrapper = mountInfo({ client: createAppointmentClient({ phone: '+84999888777' }) })
      expect(wrapper.text()).toContain('+84999888777')
    })
  })

  describe('linkable prop', () => {
    it('renders name as plain text when linkable is false (default)', () => {
      const wrapper = mountInfo()
      expect(wrapper.find('a').exists()).toBe(false)
      expect(wrapper.find('p').exists()).toBe(true)
    })

    it('renders name as NuxtLink when linkable is true', () => {
      const client = createAppointmentClient({ id: 'c-42' })
      const wrapper = mountInfo({ client, linkable: true })
      const link = wrapper.find('a')
      expect(link.exists()).toBe(true)
      expect(link.attributes('href')).toContain('c-42')
    })
  })
})
