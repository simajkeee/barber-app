import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import QuickActions from '~/components/appointment/QuickActions.vue'
import type { AppointmentStatus } from '~/types/appointment'

const UiButtonStub = {
  template: '<button :disabled="loading" @click="$emit(\'click\')"><slot /></button>',
  props: ['variant', 'size', 'loading'],
  emits: ['click'],
}

function mountActions(status: AppointmentStatus, loading = false) {
  return mount(QuickActions, {
    props: { status, loading },
    global: { stubs: { UiButton: UiButtonStub } },
  })
}

describe('AppointmentQuickActions', () => {
  describe('scheduled status', () => {
    it('shows all 4 action buttons', () => {
      const wrapper = mountActions('scheduled')
      const buttons = wrapper.findAll('button')
      expect(buttons).toHaveLength(4)
    })

    it('shows view, complete, noShow, cancel labels', () => {
      const wrapper = mountActions('scheduled')
      const text = wrapper.text()
      expect(text).toContain('appointments.actions.view')
      expect(text).toContain('appointments.actions.complete')
      expect(text).toContain('appointments.actions.noShow')
      expect(text).toContain('appointments.actions.cancel')
    })
  })

  describe('terminal statuses', () => {
    it.each<AppointmentStatus>(['completed', 'cancelled', 'no_show'])(
      'shows only view button for %s status',
      (status) => {
        const wrapper = mountActions(status)
        expect(wrapper.findAll('button')).toHaveLength(1)
        expect(wrapper.text()).toContain('appointments.actions.view')
      },
    )
  })

  describe('emits', () => {
    it('emits view when view button clicked', async () => {
      const wrapper = mountActions('scheduled')
      await wrapper.findAll('button')[0].trigger('click')
      expect(wrapper.emitted('view')).toHaveLength(1)
    })

    it('emits complete when complete button clicked', async () => {
      const wrapper = mountActions('scheduled')
      await wrapper.findAll('button')[1].trigger('click')
      expect(wrapper.emitted('complete')).toHaveLength(1)
    })

    it('emits noShow when noShow button clicked', async () => {
      const wrapper = mountActions('scheduled')
      await wrapper.findAll('button')[2].trigger('click')
      expect(wrapper.emitted('noShow')).toHaveLength(1)
    })

    it('emits cancel when cancel button clicked', async () => {
      const wrapper = mountActions('scheduled')
      await wrapper.findAll('button')[3].trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })
  })

  describe('loading state', () => {
    it('disables buttons when loading is true', () => {
      const wrapper = mountActions('scheduled', true)
      wrapper.findAll('button').forEach((btn) => {
        expect(btn.attributes('disabled')).toBeDefined()
      })
    })
  })
})
