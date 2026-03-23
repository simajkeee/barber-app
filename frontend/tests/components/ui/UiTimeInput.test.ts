import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiTimeInput from '~/components/ui/UiTimeInput.vue'

function mountInput(props: Record<string, any> = {}) {
  return mount(UiTimeInput, {
    props: { label: 'Start time', modelValue: '09:00', ...props },
  })
}

describe('UiTimeInput', () => {
  describe('rendering', () => {
    it('renders label text', () => {
      const wrapper = mountInput({ label: 'Open time' })
      expect(wrapper.find('label').text()).toBe('Open time')
    })

    it('links label to trigger button via id', () => {
      const wrapper = mountInput()
      const labelFor = wrapper.find('label').attributes('for')
      const buttonId = wrapper.find('button').attributes('id')
      expect(labelFor).toBe(buttonId)
    })

    it('shows current modelValue in trigger button', () => {
      const wrapper = mountInput({ modelValue: '14:30' })
      expect(wrapper.find('button').text()).toContain('14:30')
    })

    it('shows placeholder when modelValue is null', () => {
      const wrapper = mountInput({ modelValue: null })
      expect(wrapper.find('button').text()).toContain('--:--')
    })

    it('generates 72 time options from 06:00 to 23:45 in 15-min intervals when open', async () => {
      const wrapper = mountInput()
      await wrapper.find('button').trigger('click')
      const options = wrapper.findAll('[role="option"]')
      expect(options).toHaveLength(72)
      expect(options[0].text()).toBe('06:00')
      expect(options[options.length - 1].text()).toBe('23:45')
    })
  })

  describe('v-model', () => {
    it('emits update:modelValue when an option is clicked', async () => {
      const wrapper = mountInput({ modelValue: '09:00' })
      await wrapper.find('button').trigger('click')
      const option = wrapper.findAll('[role="option"]').find(o => o.text() === '10:30')
      await option!.trigger('click')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBe('10:30')
    })
  })

  describe('disabled state', () => {
    it('disables trigger button when disabled prop is true', () => {
      const wrapper = mountInput({ disabled: true })
      expect((wrapper.find('button').element as HTMLButtonElement).disabled).toBe(true)
    })
  })

  describe('error state', () => {
    it('shows error message when error prop provided', () => {
      const wrapper = mountInput({ error: 'Invalid time' })
      expect(wrapper.find('[role="alert"]').text()).toBe('Invalid time')
    })

    it('does not show error when no error prop', () => {
      const wrapper = mountInput()
      expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    })

    it('sets aria-invalid on trigger button when error present', () => {
      const wrapper = mountInput({ error: 'Bad' })
      expect(wrapper.find('button').attributes('aria-invalid')).toBe('true')
    })

    it('links error via aria-describedby on trigger button', () => {
      const wrapper = mountInput({ error: 'Bad' })
      const errorId = wrapper.find('[role="alert"]').attributes('id')
      expect(wrapper.find('button').attributes('aria-describedby')).toBe(errorId)
    })
  })
})
