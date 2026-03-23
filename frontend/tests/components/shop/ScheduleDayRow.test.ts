import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ScheduleDayRow from '~/components/shop/ScheduleDayRow.vue'
import type { ScheduleEntryForm } from '~/types/shop'
import { createScheduleEntry } from '../../factories'

function createEntry(overrides: Partial<ScheduleEntryForm> = {}): ScheduleEntryForm {
  const base = createScheduleEntry(overrides)
  return {
    ...base,
    openTime: base.openTime ?? '09:00',
    closeTime: base.closeTime ?? '18:00',
  }
}

function mountRow(props: Record<string, any> = {}) {
  return mount(ScheduleDayRow, {
    props: {
      dayOfWeek: 'monday',
      modelValue: createEntry(),
      ...props,
    },
    global: {
      stubs: {
        UiToggle: {
          template: '<button role="switch" :aria-checked="modelValue" @click="$emit(\'update:modelValue\', !modelValue)">{{ label }}</button>',
          props: ['modelValue', 'label'],
          emits: ['update:modelValue'],
        },
        UiTimeInput: {
          template: '<select :data-label="label"><option>{{ modelValue }}</option></select>',
          props: ['modelValue', 'label'],
          emits: ['update:modelValue'],
        },
      },
    },
  })
}

describe('ScheduleDayRow', () => {
  describe('rendering', () => {
    it('displays translated day name', () => {
      const wrapper = mountRow({ dayOfWeek: 'wednesday' })
      expect(wrapper.text()).toContain('shop.schedule.days.wednesday')
    })

    it('shows toggle with open label when open', () => {
      const wrapper = mountRow({ modelValue: createEntry({ isOpen: true }) })
      expect(wrapper.find('[role="switch"]').text()).toContain('shop.schedule.open')
    })

    it('shows toggle with closed label when closed', () => {
      const wrapper = mountRow({ modelValue: createEntry({ isOpen: false }) })
      expect(wrapper.find('[role="switch"]').text()).toContain('shop.schedule.closed')
    })
  })

  describe('time inputs', () => {
    it('shows time inputs when open', () => {
      const wrapper = mountRow({ modelValue: createEntry({ isOpen: true }) })
      const selects = wrapper.findAll('select')
      expect(selects).toHaveLength(2)
    })

    it('hides time inputs when closed', () => {
      const wrapper = mountRow({ modelValue: createEntry({ isOpen: false }) })
      const timeContainer = wrapper.find('.flex.gap-4')
      expect(timeContainer.classes()).toContain('invisible')
    })
  })

  describe('error display', () => {
    it('shows error message when error prop provided', () => {
      const wrapper = mountRow({ error: 'Open must be before close' })
      expect(wrapper.find('[role="alert"]').text()).toBe('Open must be before close')
    })

    it('does not show error when no error prop', () => {
      const wrapper = mountRow()
      expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    })
  })
})