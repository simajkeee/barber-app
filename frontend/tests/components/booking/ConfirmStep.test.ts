import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ConfirmStep from '~/components/booking/ConfirmStep.vue'
import type { PublicService } from '~/types/booking'

const service: PublicService = { id: 'svc-1', name: 'Haircut', duration: 30, price: 100000 }

function mountConfirm(overrides = {}) {
  return mount(ConfirmStep, {
    props: {
      service,
      date: '2026-03-16',
      time: '10:00',
      clientName: 'Nguyen Van A',
      clientPhone: '0901234567',
      submitting: false,
      ...overrides,
    },
  })
}

describe('ConfirmStep', () => {
  it('displays booking summary', () => {
    const wrapper = mountConfirm()

    expect(wrapper.text()).toContain('Haircut')
    expect(wrapper.text()).toContain('10:00')
    expect(wrapper.text()).toContain('Nguyen Van A')
    expect(wrapper.text()).toContain('0901234567')
  })

  it('emits confirm on submit click', async () => {
    const wrapper = mountConfirm()

    const buttons = wrapper.findAll('button')
    const confirmBtn = buttons.find(b => b.text().includes('booking.confirm.submit'))
    await confirmBtn!.trigger('click')

    expect(wrapper.emitted('confirm')).toHaveLength(1)
  })

  it('emits back on back click', async () => {
    const wrapper = mountConfirm()

    const buttons = wrapper.findAll('button')
    const backBtn = buttons.find(b => b.text().includes('common.back'))
    await backBtn!.trigger('click')

    expect(wrapper.emitted('back')).toHaveLength(1)
  })

  it('disables buttons when submitting', () => {
    const wrapper = mountConfirm({ submitting: true })

    const buttons = wrapper.findAll('button')
    buttons.forEach(btn => {
      expect(btn.attributes('disabled')).toBeDefined()
    })
  })

  it('shows loading spinner when submitting', () => {
    const wrapper = mountConfirm({ submitting: true })

    expect(wrapper.text()).toContain('booking.confirm.submitting')
  })
})
