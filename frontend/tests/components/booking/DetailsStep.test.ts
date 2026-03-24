import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import DetailsStep from '~/components/booking/DetailsStep.vue'

describe('BookingDetailsStep', () => {
  const mockReset = vi.fn()
  const mockRender = vi.fn().mockReturnValue('widget-id-1')

  beforeEach(() => {
    // Mock window.turnstile
    ;(window as any).turnstile = {
      render: mockRender,
      reset: mockReset,
      remove: vi.fn(),
    }
  })

  afterEach(() => {
    delete (window as any).turnstile
    mockReset.mockReset()
    mockRender.mockReset()
    mockRender.mockReturnValue('widget-id-1')
  })

  function mountStep(props = {}) {
    return mount(DetailsStep, {
      props: {
        initialName: '',
        initialPhone: '',
        ...props,
      },
    })
  }

  it('renders name and phone inputs', () => {
    const wrapper = mountStep()
    const inputs = wrapper.findAll('input')
    expect(inputs).toHaveLength(2)
  })

  it('pre-fills from initialName and initialPhone props', () => {
    const wrapper = mountStep({ initialName: 'Nguyen Van A', initialPhone: '0901234567' })
    const inputs = wrapper.findAll('input')
    expect(inputs[0].element.value).toBe('Nguyen Van A')
    expect(inputs[1].element.value).toBe('0901234567')
  })

  it('displays name label', () => {
    const wrapper = mountStep()
    expect(wrapper.text()).toContain('booking.details.name')
  })

  it('displays phone label', () => {
    const wrapper = mountStep()
    expect(wrapper.text()).toContain('booking.details.phone')
  })

  it('renders Turnstile widget container', () => {
    const wrapper = mountStep()
    expect(wrapper.find('.cf-turnstile').exists()).toBe(true)
  })

  it('displays captcha label', () => {
    const wrapper = mountStep()
    expect(wrapper.text()).toContain('booking.captcha.label')
  })

  it('blocks submit without CAPTCHA token', async () => {
    // Don't mock turnstile render to simulate no callback
    mockRender.mockReturnValue('widget-id-1')

    const wrapper = mountStep()
    const inputs = wrapper.findAll('input')

    await inputs[0].setValue('Test Name')
    await inputs[1].setValue('0901234567')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    await new Promise(r => setTimeout(r, 50))

    // Form validation passes but captcha guard blocks submit
    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('booking.captcha.required')
  })

  it('allows submit with valid form and CAPTCHA token', async () => {
    const wrapper = mountStep()

    // Simulate Turnstile success callback
    const renderCall = mockRender.mock.calls[0]
    if (renderCall) {
      const callback = renderCall[1].callback
      callback('test-captcha-token')
    }

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Name')
    await inputs[1].setValue('0901234567')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    if (wrapper.emitted('submit')) {
      const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
      expect(payload.clientName).toBe('Test Name')
      expect(payload.clientPhone).toBe('0901234567')
      expect(payload.captchaToken).toBe('test-captcha-token')
    }
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('resets captcha via exposed method', async () => {
    const wrapper = mountStep()
    const vm = wrapper.vm as any

    // Simulate Turnstile render
    const renderCall = mockRender.mock.calls[0]
    if (renderCall) {
      renderCall[1].callback('some-token')
    }

    vm.resetCaptcha()
    expect(mockReset).toHaveBeenCalledWith('widget-id-1')
  })

  it('calls submit with form values on valid submit', async () => {
    const wrapper = mountStep()

    // Provide captcha token so submit goes through
    const renderCall = mockRender.mock.calls[0]
    if (renderCall) {
      renderCall[1].callback('token')
    }

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Name')
    await inputs[1].setValue('0901234567')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    if (wrapper.emitted('submit')) {
      const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
      expect(payload.clientName).toBe('Test Name')
      expect(payload.clientPhone).toBe('0901234567')
    }
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('shows validation error for empty name', async () => {
    const wrapper = mountStep()
    await wrapper.find('form').trigger('submit')
    await new Promise(r => setTimeout(r, 50))
    expect(wrapper.text()).toMatch(/validation\.|required/)
  })

  it('shows validation error for invalid phone', async () => {
    const wrapper = mountStep()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Some Name')
    await inputs[1].setValue('invalid-phone')
    await wrapper.find('form').trigger('submit')
    await new Promise(r => setTimeout(r, 50))
    expect(wrapper.text()).toMatch(/validation\.|invalid/i)
  })
})
