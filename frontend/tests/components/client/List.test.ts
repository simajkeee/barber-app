import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import List from '~/components/client/List.vue'
import { createClient } from '../../factories'

function mountList(props: Record<string, any> = {}) {
  return mount(List, {
    props: {
      clients: [createClient()],
      ...props,
    },
    global: {
      stubs: {
        ClientCard: {
          template: '<div class="client-card" @click="$emit(\'view\', client.id)">{{ client.firstName }}</div>',
          props: ['client'],
          emits: ['view'],
        },
        UiEmptyState: {
          template: '<div class="empty-state"><h3>{{ title }}</h3><slot name="action" /></div>',
          props: ['title', 'description'],
        },
        UiButton: { template: '<button><slot /></button>' },
        NuxtLink: { template: '<a :href="to"><slot /></a>', props: ['to'] },
      },
    },
  })
}

describe('ClientList', () => {
  describe('loading state', () => {
    it('shows skeleton cards when loading', () => {
      const wrapper = mountList({ clients: [], isLoading: true })
      const skeletons = wrapper.findAll('.animate-pulse')
      expect(skeletons).toHaveLength(3)
    })

    it('does not show client cards when loading', () => {
      const wrapper = mountList({ clients: [createClient()], isLoading: true })
      expect(wrapper.findAll('.client-card')).toHaveLength(0)
    })
  })

  describe('empty states', () => {
    it('shows search empty state when no clients and search is active', () => {
      const wrapper = mountList({ clients: [], hasSearchFilter: true })
      expect(wrapper.find('.empty-state h3').text()).toBe('clients.list.noResults')
    })

    it('shows general empty state with CTA when no clients and no search', () => {
      const wrapper = mountList({ clients: [], hasSearchFilter: false })
      expect(wrapper.find('.empty-state h3').text()).toBe('clients.list.empty')
      expect(wrapper.find('a').exists()).toBe(true)
    })
  })

  describe('client list', () => {
    it('renders a ClientCard for each client', () => {
      const clients = [createClient({ id: '1' }), createClient({ id: '2' }), createClient({ id: '3' })]
      const wrapper = mountList({ clients })
      expect(wrapper.findAll('.client-card')).toHaveLength(3)
    })

    it('propagates view event from card', async () => {
      const wrapper = mountList({ clients: [createClient({ id: 'c-7' })] })
      await wrapper.find('.client-card').trigger('click')
      expect(wrapper.emitted('view')![0][0]).toBe('c-7')
    })
  })
})