import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import DateTimeStep from '~/components/booking/DateTimeStep.vue'
import { createPublicShopInfo, createAvailableSlotsResponse, createAvailableSlot } from '~/tests/factories'

const mockGetAvailableSlots = vi.fn()

vi.stubGlobal('usePublicBookingApi', () => ({
  getAvailableSlots: mockGetAvailableSlots,
}))

describe('BookingDateTimeStep', () => {
  const shop = createPublicShopInfo()

  beforeEach(() => {
    mockGetAvailableSlots.mockReset()
  })

  function mountStep(props = {}) {
    return mount(DateTimeStep, {
      props: {
        shop,
        serviceId: 'svc-1',
        slug: 'test-shop',
        selectedDate: null,
        selectedTime: null,
        ...props,
      },
    })
  }

  it('renders 30 date buttons', () => {
    const wrapper = mountStep()
    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBeGreaterThanOrEqual(30)
  })

  it('labels the first date as "Today"', () => {
    const wrapper = mountStep()
    const buttons = wrapper.findAll('button')
    expect(buttons[0].text()).toContain('booking.date.today')
  })

  it('disables closed-day buttons', () => {
    // Shop with only monday open — find sunday button (which is closed)
    const wrapper = mountStep()
    const buttons = wrapper.findAll('button')
    const closedButton = buttons.find(b => b.text().includes('booking.date.closed'))
    if (closedButton) {
      expect(closedButton.attributes('disabled')).toBeDefined()
    }
  })

  it('shows "Closed" label on closed days', () => {
    const wrapper = mountStep()
    // There should be at least one day with "Closed" in 30 days (sunday)
    expect(wrapper.text()).toContain('booking.date.closed')
  })

  it('emits selectDate and loads slots when date clicked', async () => {
    mockGetAvailableSlots.mockResolvedValue(createAvailableSlotsResponse())
    const wrapper = mountStep()

    // Click first enabled date (monday or today)
    const buttons = wrapper.findAll('button')
    const enabledButton = buttons.find(b => !b.attributes('disabled'))
    await enabledButton!.trigger('click')

    expect(wrapper.emitted('selectDate')).toBeTruthy()
    expect(mockGetAvailableSlots).toHaveBeenCalledOnce()
  })

  it('shows slot grid after date selected and slots loaded', async () => {
    const slots = [
      createAvailableSlot({ time: '09:00', available: true }),
      createAvailableSlot({ time: '09:30', available: false }),
    ]
    mockGetAvailableSlots.mockResolvedValue(createAvailableSlotsResponse(slots))

    const wrapper = mountStep({ selectedDate: '2026-03-17' })
    const buttons = wrapper.findAll('button')
    const enabledButton = buttons.find(b => !b.attributes('disabled'))
    await enabledButton!.trigger('click')
    await flushPromises()

    // Slot buttons should appear
    expect(wrapper.text()).toContain('09:00')
    expect(wrapper.text()).toContain('09:30')
  })

  it('emits selectTime when available slot clicked', async () => {
    const slots = [createAvailableSlot({ time: '10:00', available: true })]
    mockGetAvailableSlots.mockResolvedValue(createAvailableSlotsResponse(slots))

    const wrapper = mountStep({ selectedDate: '2026-03-17' })
    const dateButtons = wrapper.findAll('button')
    const enabledDate = dateButtons.find(b => !b.attributes('disabled'))
    await enabledDate!.trigger('click')
    await flushPromises()

    const slotButton = wrapper.findAll('button').find(b => b.text() === '10:00')
    expect(slotButton).toBeTruthy()
    await slotButton!.trigger('click')

    expect(wrapper.emitted('selectTime')).toBeTruthy()
    expect(wrapper.emitted('selectTime')![0]).toEqual(['10:00'])
  })

  it('shows no-slots message when slots array is empty', async () => {
    mockGetAvailableSlots.mockResolvedValue(createAvailableSlotsResponse([]))

    const wrapper = mountStep({ selectedDate: '2026-03-17' })
    const dateButtons = wrapper.findAll('button')
    const enabledDate = dateButtons.find(b => !b.attributes('disabled'))
    await enabledDate!.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('booking.slot.noSlots')
  })

  it('disables unavailable slot buttons', async () => {
    const slots = [
      createAvailableSlot({ time: '09:00', available: false }),
    ]
    mockGetAvailableSlots.mockResolvedValue(createAvailableSlotsResponse(slots))

    const wrapper = mountStep({ selectedDate: '2026-03-17' })
    const dateButtons = wrapper.findAll('button')
    const enabledDate = dateButtons.find(b => !b.attributes('disabled'))
    await enabledDate!.trigger('click')
    await flushPromises()

    const slotButton = wrapper.findAll('button').find(b => b.text() === '09:00')
    expect(slotButton?.attributes('disabled')).toBeDefined()
  })
})
