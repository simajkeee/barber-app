import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiButton from '~/components/ui/UiButton.vue'

describe('UiButton', () => {
  it('renders slot content', () => {
    const wrapper = mount(UiButton, { slots: { default: 'Click me' } })
    expect(wrapper.text()).toContain('Click me')
  })

  it('defaults to type=button', () => {
    const wrapper = mount(UiButton)
    expect(wrapper.find('button').attributes('type')).toBe('button')
  })

  it('applies submit type', () => {
    const wrapper = mount(UiButton, { props: { type: 'submit' } })
    expect(wrapper.find('button').attributes('type')).toBe('submit')
  })

  it('is disabled when disabled prop is true', () => {
    const wrapper = mount(UiButton, { props: { disabled: true } })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('is disabled when loading', () => {
    const wrapper = mount(UiButton, { props: { loading: true } })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    expect(wrapper.find('button').attributes('aria-busy')).toBe('true')
  })

  it('shows spinner when loading', () => {
    const wrapper = mount(UiButton, { props: { loading: true } })
    expect(wrapper.find('svg').exists()).toBe(true)
  })

  it('does not show spinner by default', () => {
    const wrapper = mount(UiButton)
    expect(wrapper.find('svg').exists()).toBe(false)
  })

  it('emits click when clicked', async () => {
    const wrapper = mount(UiButton)
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('applies full-width class', () => {
    const wrapper = mount(UiButton, { props: { fullWidth: true } })
    expect(wrapper.find('button').classes()).toContain('w-full')
  })
})