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

    it('links label to select via id', () => {
      const wrapper = mountInput()
      const labelFor = wrapper.find('label').attributes('for')
      const selectId = wrapper.find('select').attributes('id')
      expect(labelFor).toBe(selectId)
    })

    it('generates time options from 06:00 to 23:45 in 15-min intervals', () => {
      const wrapper = mountInput()
      const options = wrapper.findAll('option').filter(o => o.attributes('value') !== '')
      // 6:00 to 23:45 = 18 hours * 4 = 72 options
      expect(options).toHaveLength(72)
      expect(options[0].text()).toBe('06:00')
      expect(options[options.length - 1].text()).toBe('23:45')
    })

    it('has disabled placeholder option', () => {
      const wrapper = mountInput()
      const placeholder = wrapper.find('option[value=""]')
      expect(placeholder.exists()).toBe(true)
      expect(placeholder.attributes('disabled')).toBeDefined()
      expect(placeholder.text()).toBe('--:--')
    })

    it('selects current modelValue', () => {
      const wrapper = mountInput({ modelValue: '14:30' })
      expect((wrapper.find('select').element as HTMLSelectElement).value).toBe('14:30')
    })
  })

  describe('v-model', () => {
    it('emits update:modelValue on change', async () => {
      const wrapper = mountInput({ modelValue: '09:00' })
      await wrapper.find('select').setValue('10:30')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBe('10:30')
    })

    it('emits null when empty option selected', async () => {
      const wrapper = mountInput({ modelValue: '09:00' })
      await wrapper.find('select').setValue('')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBeNull()
    })
  })

  describe('disabled state', () => {
    it('disables select when disabled prop is true', () => {
      const wrapper = mountInput({ disabled: true })
      expect(wrapper.find('select').attributes('disabled')).toBeDefined()
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

    it('sets aria-invalid when error present', () => {
      const wrapper = mountInput({ error: 'Bad' })
      expect(wrapper.find('select').attributes('aria-invalid')).toBe('true')
    })

    it('links error via aria-describedby', () => {
      const wrapper = mountInput({ error: 'Bad' })
      const errorId = wrapper.find('[role="alert"]').attributes('id')
      expect(wrapper.find('select').attributes('aria-describedby')).toBe(errorId)
    })
  })
})