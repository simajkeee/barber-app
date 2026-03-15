import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ListFilters from '~/components/appointment/ListFilters.vue'
import type { AppointmentListFilter } from '~/types/appointment'

const UiButtonStub = {
  template: '<button class="ui-btn" @click="$emit(\'click\')"><slot /></button>',
  props: ['variant', 'size'],
  emits: ['click'],
}

const StatusFilterStub = {
  template: '<div class="status-filter"><button class="status-toggle" @click="$emit(\'update:modelValue\', [\'scheduled\'])">toggle</button></div>',
  props: ['modelValue'],
  emits: ['update:modelValue'],
}

function mountFilters(filters: AppointmentListFilter = {}) {
  return mount(ListFilters, {
    props: { filters },
    global: {
      stubs: {
        UiButton: UiButtonStub,
        AppointmentStatusFilter: StatusFilterStub,
      },
    },
  })
}

describe('AppointmentListFilters', () => {
  describe('date filters', () => {
    it('emits update:filters with dateFrom when dateFrom input changes', async () => {
      const wrapper = mountFilters({})
      const dateInputs = wrapper.findAll('input[type="date"]')
      await dateInputs[0].setValue('2026-03-01')
      await dateInputs[0].trigger('change')
      const emitted = wrapper.emitted('update:filters')
      expect(emitted).toBeTruthy()
      const lastEmit = emitted![emitted!.length - 1][0] as AppointmentListFilter
      expect(lastEmit.dateFrom).toBe('2026-03-01')
    })

    it('emits update:filters with dateTo when dateTo input changes', async () => {
      const wrapper = mountFilters({})
      const dateInputs = wrapper.findAll('input[type="date"]')
      await dateInputs[1].setValue('2026-03-31')
      await dateInputs[1].trigger('change')
      const emitted = wrapper.emitted('update:filters')
      expect(emitted).toBeTruthy()
      const lastEmit = emitted![emitted!.length - 1][0] as AppointmentListFilter
      expect(lastEmit.dateTo).toBe('2026-03-31')
    })
  })

  describe('clear filters button', () => {
    it('shows clear button when filters are active', () => {
      const wrapper = mountFilters({ dateFrom: '2026-03-01' })
      expect(wrapper.text()).toContain('appointments.filters.clearFilters')
    })

    it('hides clear button when no filters applied', () => {
      const wrapper = mountFilters({})
      // The UiButton (clear) is not rendered when hasFilters is false
      expect(wrapper.find('.ui-btn').exists()).toBe(false)
    })

    it('emits update:filters with empty object when clear button clicked', async () => {
      const wrapper = mountFilters({ dateFrom: '2026-03-01' })
      await wrapper.find('.ui-btn').trigger('click')
      const emitted = wrapper.emitted('update:filters')!
      expect(emitted[emitted.length - 1][0]).toEqual({})
    })
  })

  describe('status filter integration', () => {
    it('emits update:filters with status when StatusFilter emits', async () => {
      const wrapper = mountFilters({})
      // StatusFilterStub emits update:modelValue with ['scheduled'] on button click
      await wrapper.find('.status-toggle').trigger('click')
      const emitted = wrapper.emitted('update:filters')
      expect(emitted).toBeTruthy()
      const lastEmit = emitted![emitted!.length - 1][0] as AppointmentListFilter
      expect(lastEmit.status).toEqual(['scheduled'])
    })
  })
})
