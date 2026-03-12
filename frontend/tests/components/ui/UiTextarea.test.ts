import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiTextarea from '~/components/ui/UiTextarea.vue'

function mountTextarea(props: Record<string, any> = {}) {
  return mount(UiTextarea, {
    props: { label: 'Description', modelValue: '', 'onUpdate:modelValue': () => {}, ...props },
  })
}

describe('UiTextarea', () => {
  it('renders label text', () => {
    const wrapper = mountTextarea()
    expect(wrapper.find('label').text()).toContain('Description')
  })

  it('renders required indicator', () => {
    const wrapper = mountTextarea({ required: true })
    expect(wrapper.find('label').text()).toContain('*')
  })

  it('does not render required indicator by default', () => {
    const wrapper = mountTextarea()
    expect(wrapper.find('label').text()).not.toContain('*')
  })

  it('links label to textarea via id', () => {
    const wrapper = mountTextarea()
    const labelFor = wrapper.find('label').attributes('for')
    const textareaId = wrapper.find('textarea').attributes('id')
    expect(labelFor).toBe(textareaId)
  })

  it('applies placeholder', () => {
    const wrapper = mountTextarea({ placeholder: 'Enter text...' })
    expect(wrapper.find('textarea').attributes('placeholder')).toBe('Enter text...')
  })

  it('sets rows attribute', () => {
    const wrapper = mountTextarea({ rows: 5 })
    expect(wrapper.find('textarea').attributes('rows')).toBe('5')
  })

  it('defaults to 3 rows', () => {
    const wrapper = mountTextarea()
    expect(wrapper.find('textarea').attributes('rows')).toBe('3')
  })

  it('disables textarea when disabled', () => {
    const wrapper = mountTextarea({ disabled: true })
    expect(wrapper.find('textarea').attributes('disabled')).toBeDefined()
  })

  it('shows error message', () => {
    const wrapper = mountTextarea({ error: 'Too long' })
    expect(wrapper.find('[role="alert"]').text()).toBe('Too long')
  })

  it('does not show error when none', () => {
    const wrapper = mountTextarea()
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('sets aria-invalid when error', () => {
    const wrapper = mountTextarea({ error: 'Required' })
    expect(wrapper.find('textarea').attributes('aria-invalid')).toBe('true')
  })

  it('links textarea to error via aria-describedby', () => {
    const wrapper = mountTextarea({ error: 'Required' })
    const describedBy = wrapper.find('textarea').attributes('aria-describedby')
    const errorId = wrapper.find('[role="alert"]').attributes('id')
    expect(describedBy).toBe(errorId)
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(UiTextarea, {
      props: { label: 'Desc', modelValue: '' },
    })
    await wrapper.find('textarea').setValue('hello')
    expect(wrapper.emitted('update:modelValue')![0]).toEqual(['hello'])
  })
})