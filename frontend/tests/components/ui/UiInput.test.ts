import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import UiInput from '~/components/ui/UiInput.vue'

vi.stubGlobal('useId', () => 'test-id')

describe('UiInput', () => {
  function mountInput(props: Record<string, any> = {}) {
    return mount(UiInput, {
      props: {
        label: 'Email',
        modelValue: '',
        'onUpdate:modelValue': () => {},
        ...props,
      },
    })
  }

  it('renders label text', () => {
    const wrapper = mountInput()
    expect(wrapper.find('label').text()).toContain('Email')
  })

  it('links label to input via for/id', () => {
    const wrapper = mountInput()
    const label = wrapper.find('label')
    const input = wrapper.find('input')
    expect(label.attributes('for')).toBe(input.attributes('id'))
  })

  it('shows required asterisk when required', () => {
    const wrapper = mountInput({ required: true })
    expect(wrapper.find('span').text()).toBe('*')
  })

  it('does not show asterisk when not required', () => {
    const wrapper = mountInput()
    expect(wrapper.find('span').exists()).toBe(false)
  })

  it('applies input type', () => {
    const wrapper = mountInput({ type: 'password' })
    expect(wrapper.find('input').attributes('type')).toBe('password')
  })

  it('shows error message when error prop is set', () => {
    const wrapper = mountInput({ error: 'Required field' })
    const errorEl = wrapper.find('[role="alert"]')
    expect(errorEl.exists()).toBe(true)
    expect(errorEl.text()).toBe('Required field')
  })

  it('does not show error when no error prop', () => {
    const wrapper = mountInput()
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('sets aria-invalid when error exists', () => {
    const wrapper = mountInput({ error: 'Bad value' })
    expect(wrapper.find('input').attributes('aria-invalid')).toBe('true')
  })

  it('sets aria-describedby linking to error id', () => {
    const wrapper = mountInput({ error: 'Bad value' })
    const input = wrapper.find('input')
    const errorEl = wrapper.find('[role="alert"]')
    expect(input.attributes('aria-describedby')).toBe(errorEl.attributes('id'))
  })

  it('sets aria-invalid to false when no error', () => {
    const wrapper = mountInput()
    expect(wrapper.find('input').attributes('aria-invalid')).toBe('false')
  })

  it('does not set aria-describedby when no error', () => {
    const wrapper = mountInput()
    expect(wrapper.find('input').attributes('aria-describedby')).toBeUndefined()
  })

  it('applies autocomplete attribute', () => {
    const wrapper = mountInput({ autocomplete: 'email' })
    expect(wrapper.find('input').attributes('autocomplete')).toBe('email')
  })

  it('applies placeholder attribute', () => {
    const wrapper = mountInput({ placeholder: 'Enter email' })
    expect(wrapper.find('input').attributes('placeholder')).toBe('Enter email')
  })

  it('is disabled when disabled prop is true', () => {
    const wrapper = mountInput({ disabled: true })
    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mountInput()
    await wrapper.find('input').setValue('test@example.com')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['test@example.com'])
  })
})