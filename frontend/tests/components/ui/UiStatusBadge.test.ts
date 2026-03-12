import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiStatusBadge from '~/components/ui/UiStatusBadge.vue'

describe('UiStatusBadge', () => {
  it('renders label text', () => {
    const wrapper = mount(UiStatusBadge, { props: { label: 'Active', variant: 'success' } })
    expect(wrapper.text()).toContain('Active')
  })

  it.each([
    { variant: 'success', bgClass: 'bg-green-100' },
    { variant: 'error', bgClass: 'bg-red-100' },
    { variant: 'warning', bgClass: 'bg-amber-100' },
    { variant: 'info', bgClass: 'bg-blue-100' },
    { variant: 'neutral', bgClass: 'bg-gray-100' },
  ] as const)('applies $bgClass for $variant variant', ({ variant, bgClass }) => {
    const wrapper = mount(UiStatusBadge, { props: { label: 'Test', variant } })
    expect(wrapper.find('span').classes()).toContain(bgClass)
  })

  it('renders colored dot', () => {
    const wrapper = mount(UiStatusBadge, { props: { label: 'Active', variant: 'success' } })
    const dots = wrapper.findAll('span span')
    expect(dots.length).toBeGreaterThan(0)
  })
})