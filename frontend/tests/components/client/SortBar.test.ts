import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import SortBar from '~/components/client/SortBar.vue'

function mountSortBar(props: Record<string, any> = {}) {
  return mount(SortBar, {
    props: {
      sort: 'created_at',
      direction: 'desc',
      'onUpdate:sort': vi.fn(),
      'onUpdate:direction': vi.fn(),
      ...props,
    },
  })
}

describe('ClientSortBar', () => {
  describe('rendering', () => {
    it('renders a select with 3 sort options', () => {
      const wrapper = mountSortBar()
      const options = wrapper.findAll('option')
      expect(options).toHaveLength(3)
    })

    it('has created_at, last_visit_at, last_name options', () => {
      const wrapper = mountSortBar()
      const values = wrapper.findAll('option').map(o => o.attributes('value'))
      expect(values).toEqual(['created_at', 'last_visit_at', 'last_name'])
    })

    it('renders a direction toggle button', () => {
      const wrapper = mountSortBar()
      expect(wrapper.find('button').exists()).toBe(true)
    })
  })

  describe('interactions', () => {
    it('emits update:sort when select changes', async () => {
      const wrapper = mountSortBar()
      await wrapper.find('select').setValue('last_name')
      expect(wrapper.emitted('update:sort')![0][0]).toBe('last_name')
    })

    it('emits update:direction toggling desc to asc', async () => {
      const wrapper = mountSortBar({ direction: 'desc' })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('update:direction')![0][0]).toBe('asc')
    })

    it('emits update:direction toggling asc to desc', async () => {
      const wrapper = mountSortBar({ direction: 'asc' })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('update:direction')![0][0]).toBe('desc')
    })
  })

  describe('accessibility', () => {
    it('has aria-label on direction button for descending', () => {
      const wrapper = mountSortBar({ direction: 'desc' })
      expect(wrapper.find('button').attributes('aria-label')).toBe('clients.sort.descending')
    })

    it('has aria-label on direction button for ascending', () => {
      const wrapper = mountSortBar({ direction: 'asc' })
      expect(wrapper.find('button').attributes('aria-label')).toBe('clients.sort.ascending')
    })
  })
})