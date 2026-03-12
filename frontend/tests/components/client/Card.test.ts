import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Card from '~/components/client/Card.vue'
import { createClient } from '../../factories'

function mountCard(clientOverrides: Record<string, any> = {}) {
  return mount(Card, {
    props: { client: createClient(clientOverrides) },
    global: {
      stubs: {
        UiStatusBadge: { template: '<span>{{ label }}</span>', props: ['label', 'variant'] },
      },
    },
  })
}

describe('ClientCard', () => {
  describe('rendering', () => {
    it('displays full name', () => {
      const wrapper = mountCard({ firstName: 'Tran', lastName: 'Minh' })
      expect(wrapper.text()).toContain('Tran')
      expect(wrapper.text()).toContain('Minh')
    })

    it('displays phone number', () => {
      const wrapper = mountCard({ phone: '+84999888777' })
      expect(wrapper.text()).toContain('+84999888777')
    })

    it('displays email when present', () => {
      const wrapper = mountCard({ email: 'test@mail.com' })
      expect(wrapper.text()).toContain('test@mail.com')
    })

    it('does not display email when null', () => {
      const wrapper = mountCard({ email: null })
      expect(wrapper.text()).not.toContain('@')
    })

    it('shows visit count badge when visitCount > 0', () => {
      const wrapper = mountCard({ visitCount: 5 })
      expect(wrapper.text()).toContain('clients.list.clientCount')
    })

    it('hides visit count badge when visitCount is 0', () => {
      const wrapper = mountCard({ visitCount: 0 })
      expect(wrapper.text()).not.toContain('clients.list.clientCount')
    })

    it('displays last visit date when present', () => {
      const wrapper = mountCard({ lastVisitAt: '2026-03-10T10:00:00Z' })
      // formatDate is stubbed in setup.ts to return toLocaleDateString()
      expect(wrapper.text()).not.toContain('clients.detail.noVisits')
    })

    it('displays "no visits" when lastVisitAt is null', () => {
      const wrapper = mountCard({ lastVisitAt: null })
      expect(wrapper.text()).toContain('clients.detail.noVisits')
    })
  })

  describe('interactions', () => {
    it('emits view with client id when clicked', async () => {
      const wrapper = mountCard({ id: 'c-42' })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('view')![0][0]).toBe('c-42')
    })
  })

  describe('accessibility', () => {
    it('renders as a button element', () => {
      const wrapper = mountCard()
      expect(wrapper.find('button').exists()).toBe(true)
    })
  })
})