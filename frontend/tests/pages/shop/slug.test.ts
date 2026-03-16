import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { FetchError } from 'ofetch'
import { createPublicShopInfo, createBookingResponse } from '../../factories'

const mockGetShopInfo = vi.fn()
const mockCreateBooking = vi.fn()

vi.stubGlobal('usePublicBookingApi', () => ({
  getShopInfo: mockGetShopInfo,
  getAvailableSlots: vi.fn().mockResolvedValue({ date: '2026-03-20', slots: [] }),
  createBooking: mockCreateBooking,
}))

vi.stubGlobal('useRoute', () => ({ params: { slug: 'test-shop' } }))

const { default: SlugPage } = await import('~/pages/shop/[slug].vue')

const BookingServiceStepStub = {
  template: '<div class="service-step" />',
  props: ['services', 'selectedId'],
  emits: ['select'],
}

const BookingDateTimeStepStub = {
  template: '<div class="datetime-step" />',
  props: ['shop', 'serviceId', 'slug', 'selectedDate', 'selectedTime'],
  emits: ['selectDate', 'selectTime'],
}

const BookingDetailsStepStub = {
  template: '<div class="details-step" />',
  props: ['initialName', 'initialPhone'],
  emits: ['submit'],
}

const BookingConfirmStepStub = {
  template: '<div class="confirm-step" />',
  props: ['service', 'date', 'time', 'clientName', 'clientPhone', 'submitting'],
  emits: ['confirm', 'back'],
}

const BookingSuccessViewStub = {
  template: '<div class="success-view" />',
  props: ['booking', 'service'],
  emits: ['bookAnother'],
}

const pageStubs = {
  BookingShopHeader: { template: '<div class="shop-header" />', props: ['shop'] },
  BookingStepIndicator: { template: '<div class="step-indicator" />', props: ['currentStep', 'totalSteps', 'labels'] },
  BookingServiceStep: BookingServiceStepStub,
  BookingDateTimeStep: BookingDateTimeStepStub,
  BookingDetailsStep: BookingDetailsStepStub,
  BookingConfirmStep: BookingConfirmStepStub,
  BookingSuccessView: BookingSuccessViewStub,
}

describe('ShopSlugPage', () => {
  beforeEach(() => {
    mockGetShopInfo.mockReset()
    mockCreateBooking.mockReset()
  })

  function mountPage() {
    return mount(SlugPage, { global: { stubs: pageStubs } })
  }

  it('calls getShopInfo on mount with slug', async () => {
    mockGetShopInfo.mockResolvedValue(createPublicShopInfo())
    mountPage()
    await flushPromises()
    expect(mockGetShopInfo).toHaveBeenCalledWith('test-shop')
  })

  it('shows loading spinner before data loads', () => {
    mockGetShopInfo.mockReturnValue(new Promise(() => {}))
    const wrapper = mountPage()
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
  })

  it('shows shop header after load', async () => {
    mockGetShopInfo.mockResolvedValue(createPublicShopInfo())
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('.shop-header').exists()).toBe(true)
  })

  it('shows not-found message on 404', async () => {
    const error = new FetchError('Not found')
    ;(error as any).response = { status: 404 }
    mockGetShopInfo.mockRejectedValue(error)

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.text()).toContain('booking.error.shopNotFound')
  })

  it('shows service step initially', async () => {
    mockGetShopInfo.mockResolvedValue(createPublicShopInfo())
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('.service-step').exists()).toBe(true)
  })

  it('advances to datetime step when service selected', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    const wrapper = mountPage()
    await flushPromises()

    const serviceStep = wrapper.findComponent(BookingServiceStepStub)
    await serviceStep.vm.$emit('select', shop.services[0])

    expect(wrapper.find('.datetime-step').exists()).toBe(true)
    expect(wrapper.find('.service-step').exists()).toBe(false)
  })

  it('advances to details step when time selected', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    const wrapper = mountPage()
    await flushPromises()

    const serviceStep = wrapper.findComponent(BookingServiceStepStub)
    await serviceStep.vm.$emit('select', shop.services[0])

    const dateTimeStep = wrapper.findComponent(BookingDateTimeStepStub)
    await dateTimeStep.vm.$emit('selectTime', '10:00')

    expect(wrapper.find('.details-step').exists()).toBe(true)
  })

  it('advances to confirm step when details submitted', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.findComponent(BookingServiceStepStub).vm.$emit('select', shop.services[0])
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectTime', '10:00')
    await wrapper.findComponent(BookingDetailsStepStub).vm.$emit('submit', {
      clientName: 'Nguyen Van A',
      clientPhone: '0901234567',
    })

    expect(wrapper.find('.confirm-step').exists()).toBe(true)
  })

  it('shows success view after successful booking', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    mockCreateBooking.mockResolvedValue(createBookingResponse())
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.findComponent(BookingServiceStepStub).vm.$emit('select', shop.services[0])
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectDate', '2026-03-20')
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectTime', '10:00')
    await wrapper.findComponent(BookingDetailsStepStub).vm.$emit('submit', {
      clientName: 'Nguyen Van A',
      clientPhone: '0901234567',
    })
    await wrapper.findComponent(BookingConfirmStepStub).vm.$emit('confirm')
    await flushPromises()

    expect(wrapper.find('.success-view').exists()).toBe(true)
  })

  it('shows SLOT_UNAVAILABLE error and returns to datetime step', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    const slotError = new FetchError('Conflict')
    ;(slotError as any).data = { code: 'SLOT_UNAVAILABLE' }
    mockCreateBooking.mockRejectedValue(slotError)

    const wrapper = mountPage()
    await flushPromises()

    await wrapper.findComponent(BookingServiceStepStub).vm.$emit('select', shop.services[0])
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectDate', '2026-03-20')
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectTime', '10:00')
    await wrapper.findComponent(BookingDetailsStepStub).vm.$emit('submit', {
      clientName: 'Test',
      clientPhone: '0901234567',
    })
    await wrapper.findComponent(BookingConfirmStepStub).vm.$emit('confirm')
    await flushPromises()

    expect(wrapper.text()).toContain('booking.error.slotUnavailable')
    expect(wrapper.find('.datetime-step').exists()).toBe(true)
  })

  it('resets state when book another clicked', async () => {
    const shop = createPublicShopInfo()
    mockGetShopInfo.mockResolvedValue(shop)
    mockCreateBooking.mockResolvedValue(createBookingResponse())
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.findComponent(BookingServiceStepStub).vm.$emit('select', shop.services[0])
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectDate', '2026-03-20')
    await wrapper.findComponent(BookingDateTimeStepStub).vm.$emit('selectTime', '10:00')
    await wrapper.findComponent(BookingDetailsStepStub).vm.$emit('submit', {
      clientName: 'Test',
      clientPhone: '0901234567',
    })
    await wrapper.findComponent(BookingConfirmStepStub).vm.$emit('confirm')
    await flushPromises()

    const successView = wrapper.findComponent(BookingSuccessViewStub)
    expect(successView.exists()).toBe(true)
    await successView.vm.$emit('bookAnother')

    expect(wrapper.find('.service-step').exists()).toBe(true)
    expect(wrapper.find('.success-view').exists()).toBe(false)
  })
})
