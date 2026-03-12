import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DetailCard from '~/components/client/DetailCard.vue'
import { createClient } from '../../factories'

function mountDetailCard(clientOverrides: Record<string, any> = {}) {
  return mount(DetailCard, {
    props: { client: createClient(clientOverrides) },
  })
}

describe('ClientDetailCard', () => {
  describe('rendering', () => {
    it('displays phone number', () => {
      const wrapper = mountDetailCard({ phone: '+84123456789' })
      expect(wrapper.text()).toContain('+84123456789')
    })

    it('displays email when present', () => {
      const wrapper = mountDetailCard({ email: 'test@mail.com' })
      expect(wrapper.text()).toContain('test@mail.com')
    })

    it('displays "no email" when email is null', () => {
      const wrapper = mountDetailCard({ email: null })
      expect(wrapper.text()).toContain('clients.detail.noEmail')
    })

    it('displays visit count', () => {
      const wrapper = mountDetailCard({ visitCount: 12 })
      expect(wrapper.text()).toContain('12')
    })

    it('displays last visit date when present', () => {
      const wrapper = mountDetailCard({ lastVisitAt: '2026-03-10T10:00:00Z' })
      expect(wrapper.text()).not.toContain('clients.detail.noVisits')
    })

    it('displays "no visits" when lastVisitAt is null', () => {
      const wrapper = mountDetailCard({ lastVisitAt: null })
      expect(wrapper.text()).toContain('clients.detail.noVisits')
    })

    it('displays notes when present', () => {
      const wrapper = mountDetailCard({ notes: 'VIP customer' })
      expect(wrapper.text()).toContain('VIP customer')
    })

    it('hides notes section when notes is null', () => {
      const wrapper = mountDetailCard({ notes: null })
      expect(wrapper.text()).not.toContain('clients.detail.notes')
    })

    it('displays createdAt and updatedAt dates', () => {
      const wrapper = mountDetailCard({
        createdAt: '2026-01-15T00:00:00Z',
        updatedAt: '2026-03-10T00:00:00Z',
      })
      expect(wrapper.text()).toContain('clients.detail.createdAt')
      expect(wrapper.text()).toContain('clients.detail.updatedAt')
    })
  })

  describe('labels', () => {
    it('renders all field labels via i18n', () => {
      const wrapper = mountDetailCard()
      expect(wrapper.text()).toContain('clients.detail.phone')
      expect(wrapper.text()).toContain('clients.detail.email')
      expect(wrapper.text()).toContain('clients.detail.visitCount')
      expect(wrapper.text()).toContain('clients.detail.lastVisitAt')
    })
  })

  describe('structure', () => {
    it('uses a dl element for semantic structure', () => {
      const wrapper = mountDetailCard()
      expect(wrapper.find('dl').exists()).toBe(true)
    })
  })
})