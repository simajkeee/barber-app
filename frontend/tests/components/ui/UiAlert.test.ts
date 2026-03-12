import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiAlert from '~/components/ui/UiAlert.vue'

describe('UiAlert', () => {
  it('renders message text', () => {
    const wrapper = mount(UiAlert, { props: { message: 'Something went wrong' } })
    expect(wrapper.text()).toContain('Something went wrong')
  })

  it('has role="alert" for accessibility', () => {
    const wrapper = mount(UiAlert, { props: { message: 'Error' } })
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
  })

  it('has aria-live="polite"', () => {
    const wrapper = mount(UiAlert, { props: { message: 'Error' } })
    expect(wrapper.find('[aria-live="polite"]').exists()).toBe(true)
  })

  it('defaults to error type', () => {
    const wrapper = mount(UiAlert, { props: { message: 'Error' } })
    const classes = wrapper.find('[role="alert"]').classes().join(' ')
    expect(classes).toContain('bg-error-light')
  })

  it.each(['error', 'success', 'warning', 'info'] as const)('applies %s styles', (type) => {
    const wrapper = mount(UiAlert, { props: { message: 'Test', type } })
    const classes = wrapper.find('[role="alert"]').classes().join(' ')
    expect(classes).toContain(`bg-${type}-light`)
  })
})