import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceFormModal from '~/components/shop/ServiceFormModal.vue'
import { createShopService } from '../../factories'

function mountModal(props: Record<string, any> = {}) {
  return mount(ServiceFormModal, {
    props: { open: true, ...props },
    global: {
      stubs: {
        UiModal: {
          template: '<div v-if="open" role="dialog"><h2>{{ title }}</h2><slot /><slot name="footer" /></div>',
          props: ['open', 'title'],
          emits: ['close'],
        },
        UiInput: {
          template: '<div><label>{{ label }}</label><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
          props: ['modelValue', 'label', 'required', 'error', 'type'],
          emits: ['update:modelValue'],
        },
        UiButton: {
          template: '<button :disabled="loading" @click="$emit(\'click\')"><slot /></button>',
          props: ['variant', 'loading'],
          emits: ['click'],
        },
        UiAlert: {
          template: '<div role="alert">{{ message }}</div>',
          props: ['message'],
        },
      },
    },
  })
}

describe('ServiceFormModal', () => {
  describe('create mode (no service prop)', () => {
    it('shows add title', () => {
      const wrapper = mountModal()
      expect(wrapper.find('h2').text()).toBe('shop.services.add')
    })

    it('renders 4 form fields', () => {
      const wrapper = mountModal()
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels).toContain('shop.form.serviceName')
      expect(labels).toContain('shop.form.duration')
      expect(labels).toContain('shop.form.price')
      expect(labels).toContain('shop.form.sortOrder')
    })
  })

  describe('edit mode (with service prop)', () => {
    it('shows edit title', () => {
      const wrapper = mountModal({ service: createShopService() })
      expect(wrapper.find('h2').text()).toBe('shop.services.edit')
    })
  })

  describe('when closed', () => {
    it('does not render dialog', () => {
      const wrapper = mountModal({ open: false })
      expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    })
  })

  describe('actions', () => {
    it('emits close on cancel button click', async () => {
      const wrapper = mountModal()
      const cancelBtn = wrapper.findAll('button').find(b => b.text().includes('common.cancel'))!
      await cancelBtn.trigger('click')
      expect(wrapper.emitted('close')).toHaveLength(1)
    })
  })

  describe('setError', () => {
    it('sets general error via exposed setError', async () => {
      const wrapper = mountModal()
      ;(wrapper.vm as any).setError('_general', 'Server error')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="alert"]').text()).toContain('Server error')
    })
  })

  describe('loading', () => {
    it('disables save button when loading', () => {
      const wrapper = mountModal({ loading: true })
      const saveBtn = wrapper.findAll('button').find(b => b.text().includes('common.save'))
      expect(saveBtn?.attributes('disabled')).toBeDefined()
    })
  })
})