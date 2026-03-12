import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceCard from '~/components/shop/ServiceCard.vue'
import { createShopService } from '../../factories'

function mountCard(serviceOverrides: Record<string, any> = {}) {
  return mount(ServiceCard, {
    props: { service: createShopService(serviceOverrides) },
    global: {
      stubs: {
        UiStatusBadge: { template: '<span>{{ label }}</span>', props: ['label', 'variant'] },
        UiButton: { template: '<button @click="$emit(\'click\')"><slot /></button>', emits: ['click'] },
      },
    },
  })
}

describe('ServiceCard', () => {
  describe('rendering', () => {
    it('displays service name', () => {
      const wrapper = mountCard({ name: 'Premium Cut' })
      expect(wrapper.find('h3').text()).toBe('Premium Cut')
    })

    it('displays formatted duration and price', () => {
      const wrapper = mountCard({ durationMinutes: 45, price: 150000 })
      expect(wrapper.text()).toContain('45 min')
      expect(wrapper.text()).toContain('150000 ₫')
    })

    it('shows active badge for active service', () => {
      const wrapper = mountCard({ isActive: true })
      expect(wrapper.text()).toContain('shop.services.active')
    })

    it('shows inactive badge for inactive service', () => {
      const wrapper = mountCard({ isActive: false })
      expect(wrapper.text()).toContain('shop.services.inactive')
    })
  })

  describe('actions', () => {
    it('emits edit with service on edit click', async () => {
      const service = createShopService({ id: 'svc-1' })
      const wrapper = mount(ServiceCard, {
        props: { service },
        global: {
          stubs: {
            UiStatusBadge: true,
            UiButton: { template: '<button @click="$emit(\'click\')"><slot /></button>', emits: ['click'] },
          },
        },
      })
      const buttons = wrapper.findAll('button')
      await buttons[0].trigger('click')
      expect(wrapper.emitted('edit')![0][0]).toEqual(service)
    })

    it('emits delete with service id on deactivate click', async () => {
      const service = createShopService({ id: 'svc-42' })
      const wrapper = mount(ServiceCard, {
        props: { service },
        global: {
          stubs: {
            UiStatusBadge: true,
            UiButton: { template: '<button @click="$emit(\'click\')"><slot /></button>', emits: ['click'] },
          },
        },
      })
      const buttons = wrapper.findAll('button')
      await buttons[1].trigger('click')
      expect(wrapper.emitted('delete')![0][0]).toBe('svc-42')
    })

    it('shows deactivate label for active service', () => {
      const wrapper = mountCard({ isActive: true })
      expect(wrapper.text()).toContain('shop.services.deactivate')
      expect(wrapper.text()).not.toContain('shop.services.activate')
    })

    it('shows activate label for inactive service', () => {
      const wrapper = mountCard({ isActive: false })
      expect(wrapper.text()).toContain('shop.services.activate')
      expect(wrapper.text()).not.toContain('shop.services.deactivate')
    })

    it('emits activate with service id on activate click', async () => {
      const service = createShopService({ id: 'svc-99', isActive: false })
      const wrapper = mount(ServiceCard, {
        props: { service },
        global: {
          stubs: {
            UiStatusBadge: true,
            UiButton: { template: '<button @click="$emit(\'click\')"><slot /></button>', emits: ['click'] },
          },
        },
      })
      const buttons = wrapper.findAll('button')
      await buttons[1].trigger('click')
      expect(wrapper.emitted('activate')![0][0]).toBe('svc-99')
    })
  })
})