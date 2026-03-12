import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import UiModal from '~/components/ui/UiModal.vue'

function mountModal(props: Record<string, any> = {}, slots: Record<string, any> = {}) {
  return mount(UiModal, {
    props: { open: true, title: 'Test Modal', ...props },
    slots: { default: '<p>Body content</p>', ...slots },
    attachTo: document.body,
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
}

describe('UiModal', () => {
  beforeEach(() => {
    document.body.style.overflow = ''
  })

  describe('when open', () => {
    it('renders dialog with title', () => {
      const wrapper = mountModal()
      expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
      expect(wrapper.find('h2').text()).toBe('Test Modal')
      wrapper.unmount()
    })

    it('renders body slot content', () => {
      const wrapper = mountModal()
      expect(wrapper.text()).toContain('Body content')
      wrapper.unmount()
    })

    it('renders footer slot when provided', () => {
      const wrapper = mountModal({}, { footer: '<button>Save</button>' })
      expect(wrapper.text()).toContain('Save')
      wrapper.unmount()
    })

    it('has aria-modal attribute', () => {
      const wrapper = mountModal()
      expect(wrapper.find('[role="dialog"]').attributes('aria-modal')).toBe('true')
      wrapper.unmount()
    })

    it('links title via aria-labelledby', () => {
      const wrapper = mountModal()
      const dialog = wrapper.find('[role="dialog"]')
      const titleId = wrapper.find('h2').attributes('id')
      expect(dialog.attributes('aria-labelledby')).toBe(titleId)
      wrapper.unmount()
    })
  })

  describe('when closed', () => {
    it('does not render dialog', () => {
      const wrapper = mountModal({ open: false })
      expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
      wrapper.unmount()
    })
  })

  describe('closing behavior', () => {
    it('emits close when close button clicked', async () => {
      const wrapper = mountModal()
      await wrapper.find('button[aria-label="Close"]').trigger('click')
      expect(wrapper.emitted('close')).toHaveLength(1)
      wrapper.unmount()
    })

    it('emits close when backdrop clicked', async () => {
      const wrapper = mountModal()
      // Backdrop is the first div inside the dialog container
      const divs = wrapper.findAll('[role="dialog"] > div')
      await divs[0].trigger('click')
      expect(wrapper.emitted('close')).toHaveLength(1)
      wrapper.unmount()
    })
  })

  describe('body overflow', () => {
    it('locks body scroll when opened', async () => {
      const wrapper = mount(UiModal, {
        props: { open: false, title: 'Test' },
        attachTo: document.body,
        global: { stubs: { Teleport: true } },
      })
      await wrapper.setProps({ open: true })
      expect(document.body.style.overflow).toBe('hidden')
      wrapper.unmount()
    })

    it('restores body scroll when closed', async () => {
      const wrapper = mount(UiModal, {
        props: { open: true, title: 'Test' },
        attachTo: document.body,
        global: { stubs: { Teleport: true } },
      })
      await wrapper.setProps({ open: false })
      expect(document.body.style.overflow).toBe('')
      wrapper.unmount()
    })

    it('restores body scroll on unmount', () => {
      const wrapper = mountModal()
      document.body.style.overflow = 'hidden'
      wrapper.unmount()
      expect(document.body.style.overflow).toBe('')
    })
  })
})