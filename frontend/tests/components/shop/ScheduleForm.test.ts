import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ScheduleForm from '~/components/shop/ScheduleForm.vue'
import { createScheduleEntry, createDefaultSchedule } from '../../factories'

function mountForm(props: Record<string, any> = {}) {
  return mount(ScheduleForm, {
    props: { schedule: createDefaultSchedule(), ...props },
    global: {
      stubs: {
        ShopScheduleDayRow: {
          template: '<div class="day-row" :data-day="dayOfWeek">{{ dayOfWeek }}</div>',
          props: ['modelValue', 'dayOfWeek', 'error'],
          emits: ['update:modelValue'],
        },
        UiButton: {
          template: '<button :type="type || \'button\'" :disabled="loading" @click="$emit(\'click\')"><slot /></button>',
          props: ['type', 'loading'],
          emits: ['click'],
        },
        UiAlert: {
          template: '<div role="alert">{{ message }}</div>',
          props: ['message'],
        },
      },
    },
  })
}

describe('ScheduleForm', () => {
  describe('rendering', () => {
    it('renders 7 day rows', () => {
      const wrapper = mountForm()
      expect(wrapper.findAll('.day-row')).toHaveLength(7)
    })

    it('renders rows for all days of the week', () => {
      const wrapper = mountForm()
      const days = wrapper.findAll('.day-row').map(r => r.attributes('data-day'))
      expect(days).toEqual(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
    })

    it('shows save button', () => {
      const wrapper = mountForm()
      expect(wrapper.text()).toContain('shop.schedule.save')
    })
  })

  describe('submit', () => {
    it('emits submit with schedule data on valid form', async () => {
      const wrapper = mountForm()
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      const emitted = wrapper.emitted('submit')
      expect(emitted).toHaveLength(1)
      const payload = emitted![0][0] as { schedule: any[] }
      expect(payload.schedule).toHaveLength(7)
      expect(payload.schedule[0].dayOfWeek).toBe('monday')
    })

    it('sets null times for closed days in submitted data', async () => {
      const wrapper = mountForm()
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      const payload = wrapper.emitted('submit')![0][0] as { schedule: any[] }
      const sunday = payload.schedule.find((s: any) => s.dayOfWeek === 'sunday')
      expect(sunday.openTime).toBeNull()
      expect(sunday.closeTime).toBeNull()
      expect(sunday.isOpen).toBe(false)
    })
  })

  describe('setGeneralError', () => {
    it('displays general error via exposed method', async () => {
      const wrapper = mountForm()
      ;(wrapper.vm as any).setGeneralError('Failed to save')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="alert"]').text()).toContain('Failed to save')
    })
  })

  describe('loading', () => {
    it('disables submit button when loading', () => {
      const wrapper = mountForm({ loading: true })
      const btn = wrapper.find('button[type="submit"]')
      expect(btn.attributes('disabled')).toBeDefined()
    })
  })

  describe('initial values from schedule prop', () => {
    it('uses existing schedule data for matching days', () => {
      const schedule = [
        createScheduleEntry({ dayOfWeek: 'monday', openTime: '07:00', closeTime: '20:00', isOpen: true }),
      ]
      const wrapper = mountForm({ schedule })
      const mondayRow = wrapper.find('[data-day="monday"]')
      expect(mondayRow.exists()).toBe(true)
    })
  })
})