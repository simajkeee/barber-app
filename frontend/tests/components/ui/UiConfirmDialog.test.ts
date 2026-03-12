import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiConfirmDialog from '~/components/ui/UiConfirmDialog.vue'

function mountDialog(props: Record<string, any> = {}) {
  return mount(UiConfirmDialog, {
    props: { open: true, title: 'Delete item?', ...props },
    global: {
      stubs: {
        UiModal: {
          template: '<div v-if="open" role="dialog"><slot /><slot name="footer" /></div>',
          props: ['open', 'title'],
          emits: ['close'],
        },
        UiButton: {
          template: '<button :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
          props: ['variant', 'loading', 'disabled'],
          emits: ['click'],
        },
      },
    },
  })
}

describe('UiConfirmDialog', () => {
  describe('rendering', () => {
    it('renders when open', () => {
      const wrapper = mountDialog()
      expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    })

    it('does not render when closed', () => {
      const wrapper = mountDialog({ open: false })
      expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    })

    it('displays description when provided', () => {
      const wrapper = mountDialog({ description: 'This cannot be undone.' })
      expect(wrapper.text()).toContain('This cannot be undone.')
    })

    it('does not display description when not provided', () => {
      const wrapper = mountDialog()
      expect(wrapper.find('p').exists()).toBe(false)
    })
  })

  describe('buttons', () => {
    it('shows default cancel and confirm labels', () => {
      const wrapper = mountDialog()
      const buttons = wrapper.findAll('button')
      expect(buttons[0].text()).toContain('common.cancel')
      expect(buttons[1].text()).toContain('common.confirm')
    })

    it('shows custom cancel and confirm labels', () => {
      const wrapper = mountDialog({ cancelLabel: 'Nope', confirmLabel: 'Yes do it' })
      const buttons = wrapper.findAll('button')
      expect(buttons[0].text()).toContain('Nope')
      expect(buttons[1].text()).toContain('Yes do it')
    })

    it('emits cancel on cancel button click', async () => {
      const wrapper = mountDialog()
      await wrapper.findAll('button')[0].trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })

    it('emits confirm on confirm button click', async () => {
      const wrapper = mountDialog()
      await wrapper.findAll('button')[1].trigger('click')
      expect(wrapper.emitted('confirm')).toHaveLength(1)
    })
  })

  describe('loading state', () => {
    it('disables cancel button when loading', () => {
      const wrapper = mountDialog({ loading: true })
      expect(wrapper.findAll('button')[0].attributes('disabled')).toBeDefined()
    })
  })
})