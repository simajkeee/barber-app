import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ClientSearchSelect from '~/components/appointment/ClientSearchSelect.vue'
import { createClient } from '../../factories'

const clients = [
  createClient({ id: 'c-1', firstName: 'Nguyen', lastName: 'Van A', phone: '0901111111' }),
  createClient({ id: 'c-2', firstName: 'Tran', lastName: 'Thi B', phone: '0902222222' }),
  createClient({ id: 'c-3', firstName: 'Le', lastName: 'Van C', phone: '0903333333' }),
]

function mountSelect(props: Record<string, any> = {}) {
  return mount(ClientSearchSelect, {
    props: { modelValue: null, clients, ...props },
    attachTo: document.body,
  })
}

describe('AppointmentClientSearchSelect', () => {
  describe('initial state (no selection)', () => {
    it('shows search input when no client selected', () => {
      const wrapper = mountSelect()
      expect(wrapper.find('input[type="text"]').exists()).toBe(true)
    })

    it('does not show client chip when nothing selected', () => {
      const wrapper = mountSelect()
      // Chip only visible when selectedClient exists
      expect(wrapper.find('button[aria-label]').exists()).toBe(false)
    })
  })

  describe('selected state', () => {
    it('shows client chip with name and phone when client selected', () => {
      const wrapper = mountSelect({ modelValue: 'c-1' })
      expect(wrapper.text()).toContain('Nguyen')
      expect(wrapper.text()).toContain('Van A')
      expect(wrapper.text()).toContain('0901111111')
    })

    it('hides search input when client selected', () => {
      const wrapper = mountSelect({ modelValue: 'c-1' })
      expect(wrapper.find('input[type="text"]').exists()).toBe(false)
    })

    it('emits update:modelValue with null when clear button clicked', async () => {
      const wrapper = mountSelect({ modelValue: 'c-1' })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBeNull()
    })
  })

  describe('dropdown', () => {
    it('opens dropdown on input focus', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      expect(wrapper.find('ul[role="listbox"]').exists()).toBe(true)
    })

    it('shows all clients initially (up to 50)', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      expect(wrapper.findAll('li[role="option"]')).toHaveLength(clients.length)
    })

    it('filters clients by first name on search input', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      await wrapper.find('input').setValue('Tran')
      const options = wrapper.findAll('li[role="option"]')
      expect(options).toHaveLength(1)
      expect(options[0].text()).toContain('Tran')
    })

    it('filters clients by phone number', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      await wrapper.find('input').setValue('0903')
      const options = wrapper.findAll('li[role="option"]')
      expect(options).toHaveLength(1)
      expect(options[0].text()).toContain('0903333333')
    })

    it('shows no results message when search finds nothing', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      await wrapper.find('input').setValue('zzznomatch')
      expect(wrapper.text()).toContain('clients.list.noResults')
    })

    it('emits update:modelValue with client id on selection', async () => {
      const wrapper = mountSelect()
      await wrapper.find('input').trigger('focus')
      await nextTick()
      await wrapper.findAll('li[role="option"]')[0].trigger('mousedown')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBe('c-1')
    })
  })

  describe('error state', () => {
    it('shows error message when error prop provided', () => {
      const wrapper = mountSelect({ error: 'Client is required' })
      expect(wrapper.text()).toContain('Client is required')
    })
  })
})
