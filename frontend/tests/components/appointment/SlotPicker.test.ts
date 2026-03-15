import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SlotPicker from '~/components/appointment/SlotPicker.vue'
import { createTimeSlot } from '../../factories'

const slot1 = createTimeSlot({ startTime: '2026-03-15T02:00:00.000Z', endTime: '2026-03-15T02:30:00.000Z' })
const slot2 = createTimeSlot({ startTime: '2026-03-15T03:00:00.000Z', endTime: '2026-03-15T03:30:00.000Z' })

function mountPicker(props: Record<string, any> = {}) {
  return mount(SlotPicker, {
    props: {
      slots: [slot1, slot2],
      modelValue: null,
      isLoading: false,
      ...props,
    },
  })
}

describe('AppointmentSlotPicker', () => {
  describe('loading state', () => {
    it('shows loading spinner when isLoading is true', () => {
      const wrapper = mountPicker({ isLoading: true })
      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })

    it('hides slot buttons when loading', () => {
      const wrapper = mountPicker({ isLoading: true })
      expect(wrapper.findAll('button[aria-pressed]')).toHaveLength(0)
    })
  })

  describe('empty state', () => {
    it('shows no slots message when slots array is empty and not loading', () => {
      const wrapper = mountPicker({ slots: [] })
      expect(wrapper.text()).toContain('appointments.form.noSlots')
    })
  })

  describe('slot buttons', () => {
    it('renders a button for each slot', () => {
      const wrapper = mountPicker()
      expect(wrapper.findAll('button[aria-pressed]')).toHaveLength(2)
    })

    it('emits update:modelValue with startTime when slot button clicked', async () => {
      const wrapper = mountPicker()
      await wrapper.findAll('button[aria-pressed]')[0].trigger('click')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBe(slot1.startTime)
    })

    it('emits null when clicking the already-selected slot (deselect)', async () => {
      const wrapper = mountPicker({ modelValue: slot1.startTime })
      await wrapper.findAll('button[aria-pressed]')[0].trigger('click')
      expect(wrapper.emitted('update:modelValue')![0][0]).toBeNull()
    })

    it('marks selected slot with aria-pressed="true"', () => {
      const wrapper = mountPicker({ modelValue: slot1.startTime })
      const buttons = wrapper.findAll('button[aria-pressed]')
      expect(buttons[0].attributes('aria-pressed')).toBe('true')
      expect(buttons[1].attributes('aria-pressed')).toBe('false')
    })
  })

  describe('error state', () => {
    it('shows error message when error prop provided', () => {
      const wrapper = mountPicker({ error: 'This slot is taken' })
      expect(wrapper.text()).toContain('This slot is taken')
    })
  })
})
