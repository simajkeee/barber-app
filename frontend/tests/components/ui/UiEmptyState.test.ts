import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiEmptyState from '~/components/ui/UiEmptyState.vue'

describe('UiEmptyState', () => {
  it('renders title', () => {
    const wrapper = mount(UiEmptyState, { props: { title: 'No items' } })
    expect(wrapper.find('h3').text()).toBe('No items')
  })

  it('renders description when provided', () => {
    const wrapper = mount(UiEmptyState, {
      props: { title: 'No items', description: 'Add one to get started' },
    })
    expect(wrapper.find('p').text()).toBe('Add one to get started')
  })

  it('does not render description when not provided', () => {
    const wrapper = mount(UiEmptyState, { props: { title: 'No items' } })
    expect(wrapper.find('p').exists()).toBe(false)
  })

  it('renders action slot when provided', () => {
    const wrapper = mount(UiEmptyState, {
      props: { title: 'No items' },
      slots: { action: '<button>Add</button>' },
    })
    expect(wrapper.find('button').text()).toBe('Add')
  })

  it('does not render action container when no slot', () => {
    const wrapper = mount(UiEmptyState, { props: { title: 'No items' } })
    const divs = wrapper.findAll('div')
    expect(divs.length).toBe(1)
  })

  it('renders an icon', () => {
    const wrapper = mount(UiEmptyState, { props: { title: 'Empty' } })
    expect(wrapper.find('svg').exists()).toBe(true)
  })
})