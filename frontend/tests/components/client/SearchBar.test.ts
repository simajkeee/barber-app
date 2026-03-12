import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SearchBar from '~/components/client/SearchBar.vue'

function mountSearchBar(modelValue = '') {
  return mount(SearchBar, {
    props: { modelValue, 'onUpdate:modelValue': vi.fn() },
    global: {
      stubs: {
        UiInput: {
          template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
          props: ['modelValue', 'placeholder', 'autocomplete'],
          emits: ['update:modelValue'],
        },
      },
    },
  })
}

describe('ClientSearchBar', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  it('renders an input field', () => {
    const wrapper = mountSearchBar()
    expect(wrapper.find('input').exists()).toBe(true)
  })

  it('debounces emit of model value by 300ms', async () => {
    const wrapper = mountSearchBar()
    await wrapper.find('input').setValue('Nguyen')

    // Before debounce fires
    expect(wrapper.emitted('update:modelValue')).toBeUndefined()

    // After debounce
    vi.advanceTimersByTime(300)
    expect(wrapper.emitted('update:modelValue')![0][0]).toBe('Nguyen')
  })

  it('shows clear button when local value is not empty', async () => {
    const wrapper = mountSearchBar()
    await wrapper.find('input').setValue('test')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('does not show clear button when empty', () => {
    const wrapper = mountSearchBar('')
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('clears local value on clear button click', async () => {
    const wrapper = mountSearchBar()
    await wrapper.find('input').setValue('test')
    expect(wrapper.find('button').exists()).toBe(true)
    await wrapper.find('button').trigger('click')
    // After clear, the clear button should disappear (localValue is empty)
    expect(wrapper.find('button').exists()).toBe(false)
  })
})