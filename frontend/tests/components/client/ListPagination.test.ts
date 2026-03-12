import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ListPagination from '~/components/client/ListPagination.vue'

function mountPagination(props: Record<string, any> = {}) {
  return mount(ListPagination, {
    props: { hasMore: true, ...props },
    global: {
      stubs: {
        UiButton: {
          template: '<button :disabled="loading" @click="$emit(\'click\')"><slot /></button>',
          props: ['variant', 'loading'],
          emits: ['click'],
        },
      },
    },
  })
}

describe('ClientListPagination', () => {
  it('renders Load More button when hasMore is true', () => {
    const wrapper = mountPagination({ hasMore: true })
    expect(wrapper.find('button').exists()).toBe(true)
    expect(wrapper.text()).toContain('clients.list.loadMore')
  })

  it('hides button when hasMore is false', () => {
    const wrapper = mountPagination({ hasMore: false })
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('emits loadMore when button clicked', async () => {
    const wrapper = mountPagination()
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('loadMore')).toHaveLength(1)
  })

  it('disables button when loading', () => {
    const wrapper = mountPagination({ isLoading: true })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })
})