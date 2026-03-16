import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReminderListPagination from '~/components/reminder/ListPagination.vue'

describe('ReminderListPagination', () => {
  function mountPagination(props: { hasMore?: boolean; isLoading?: boolean } = {}) {
    return mount(ReminderListPagination, {
      props: {
        hasMore: props.hasMore ?? false,
        isLoading: props.isLoading ?? false,
      },
      global: {
        stubs: {
          UiButton: {
            template: '<button :disabled="disabled"><slot /></button>',
            props: ['variant', 'disabled'],
          },
        },
      },
    })
  }

  it('renders nothing when hasMore is false', () => {
    const wrapper = mountPagination({ hasMore: false })
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('renders load more button when hasMore is true', () => {
    const wrapper = mountPagination({ hasMore: true })
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('shows load more label when not loading', () => {
    const wrapper = mountPagination({ hasMore: true, isLoading: false })
    expect(wrapper.text()).toContain('reminders.loadMore')
  })

  it('shows loading label when isLoading is true', () => {
    const wrapper = mountPagination({ hasMore: true, isLoading: true })
    expect(wrapper.text()).toContain('common.loading')
  })

  it('disables button when isLoading is true', () => {
    const wrapper = mountPagination({ hasMore: true, isLoading: true })
    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('emits loadMore when button clicked', async () => {
    const wrapper = mountPagination({ hasMore: true, isLoading: false })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('loadMore')).toBeTruthy()
  })
})
