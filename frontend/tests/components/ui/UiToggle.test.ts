import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiToggle from '~/components/ui/UiToggle.vue'

function mountToggle(props: Record<string, any> = {}) {
  return mount(UiToggle, {
    props: { label: 'Active', modelValue: false, 'onUpdate:modelValue': () => {}, ...props },
  })
}

describe('UiToggle', () => {
  it('renders label text', () => {
    const wrapper = mountToggle()
    expect(wrapper.text()).toContain('Active')
  })

  it('has role=switch on button', () => {
    const wrapper = mountToggle()
    expect(wrapper.find('button').attributes('role')).toBe('switch')
  })

  it('sets aria-checked to false when off', () => {
    const wrapper = mountToggle({ modelValue: false })
    expect(wrapper.find('button').attributes('aria-checked')).toBe('false')
  })

  it('sets aria-checked to true when on', () => {
    const wrapper = mountToggle({ modelValue: true })
    expect(wrapper.find('button').attributes('aria-checked')).toBe('true')
  })

  it('sets aria-label from label prop', () => {
    const wrapper = mountToggle({ label: 'Toggle me' })
    expect(wrapper.find('button').attributes('aria-label')).toBe('Toggle me')
  })

  it('emits update:modelValue with toggled value on click', async () => {
    const wrapper = mount(UiToggle, {
      props: { label: 'Active', modelValue: false },
    })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('update:modelValue')![0]).toEqual([true])
  })

  it('disables button when disabled', () => {
    const wrapper = mountToggle({ disabled: true })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })
})