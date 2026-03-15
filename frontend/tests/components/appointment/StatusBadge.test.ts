import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusBadge from '~/components/appointment/StatusBadge.vue'
import type { AppointmentStatus } from '~/types/appointment'

function mountBadge(status: AppointmentStatus) {
  return mount(StatusBadge, {
    props: { status },
    global: {
      stubs: {
        UiStatusBadge: {
          template: '<span :data-variant="variant" :data-label="label">{{ label }}</span>',
          props: ['variant', 'label'],
        },
      },
    },
  })
}

describe('AppointmentStatusBadge', () => {
  describe('variant mapping', () => {
    it.each<[AppointmentStatus, string]>([
      ['scheduled', 'info'],
      ['completed', 'success'],
      ['cancelled', 'neutral'],
      ['no_show', 'warning'],
    ])('maps %s status to %s variant', (status, variant) => {
      const wrapper = mountBadge(status)
      expect(wrapper.find('span').attributes('data-variant')).toBe(variant)
    })
  })

  describe('label', () => {
    it.each<AppointmentStatus>(['scheduled', 'completed', 'cancelled', 'no_show'])(
      'passes i18n key for %s status',
      (status) => {
        const wrapper = mountBadge(status)
        expect(wrapper.find('span').attributes('data-label')).toBe(`appointments.status.${status}`)
      },
    )
  })
})
