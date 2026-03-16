import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SuccessView from '~/components/booking/SuccessView.vue'
import { createPublicService, createBookingResponse } from '~/tests/factories'

describe('BookingSuccessView', () => {
  function mountView(overrides = {}) {
    return mount(SuccessView, {
      props: {
        booking: createBookingResponse(),
        service: createPublicService(),
        ...overrides,
      },
    })
  }

  it('displays success title', () => {
    const wrapper = mountView()
    expect(wrapper.text()).toContain('booking.success.title')
  })

  it('displays service name from booking', () => {
    const wrapper = mountView()
    expect(wrapper.text()).toContain('Haircut')
  })

  it('displays appointment time', () => {
    const wrapper = mountView()
    expect(wrapper.text()).toContain('09:00')
  })

  it('displays book another button', () => {
    const wrapper = mountView()
    const btn = wrapper.find('button')
    expect(btn.text()).toContain('booking.success.bookAnother')
  })

  it('emits bookAnother when button clicked', async () => {
    const wrapper = mountView()
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('bookAnother')).toHaveLength(1)
  })

  it('shows success icon', () => {
    const wrapper = mountView()
    // Green checkmark circle
    expect(wrapper.find('.bg-green-100').exists()).toBe(true)
  })
})
