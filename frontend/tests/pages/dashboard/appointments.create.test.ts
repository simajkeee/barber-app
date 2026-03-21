import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { FetchError } from 'ofetch'

const mockCreateAppointment = vi.fn()
vi.stubGlobal('useAppointmentApi', () => ({
  createAppointment: mockCreateAppointment,
}))

vi.stubGlobal('useApiError', () => ({
  parseApiError: (err: unknown) => {
    const data = (err as any)?.data
    return { error: data?.code ?? 'unexpected', fieldErrors: undefined }
  },
}))

const mockToastError = vi.fn()
vi.stubGlobal('useToast', () => ({ success: vi.fn(), error: mockToastError }))

const mockSetError = vi.fn()
const AppointmentFormStub = {
  template: '<div class="appointment-form"><button class="submit-btn" @click="$emit(\'submit\', {})">submit</button></div>',
  props: ['appointment', 'loading'],
  emits: ['submit', 'cancel'],
  methods: { setError: mockSetError },
}

const { default: CreatePage } = await import('~/pages/dashboard/appointments/create.vue')

const pageStubs = {
  DashboardPageHeader: { template: '<div />', props: ['title'] },
  AppointmentForm: AppointmentFormStub,
}

describe('AppointmentCreatePage — subscription error handling', () => {
  beforeEach(() => {
    mockCreateAppointment.mockReset()
    mockToastError.mockReset()
    mockSetError.mockReset()
  })

  async function submitForm() {
    const wrapper = mount(CreatePage, { global: { stubs: pageStubs } })
    await flushPromises()
    await wrapper.find('.submit-btn').trigger('click')
    await flushPromises()
    return wrapper
  }

  function makeError(code: string) {
    const err = new FetchError(code)
    ;(err as any).data = { code }
    return err
  }

  it('sets inline _general error for APPOINTMENT_LIMIT_REACHED', async () => {
    mockCreateAppointment.mockRejectedValue(makeError('APPOINTMENT_LIMIT_REACHED'))
    await submitForm()
    expect(mockSetError).toHaveBeenCalledWith('_general', 'subscription.usage.limitReached')
    expect(mockToastError).not.toHaveBeenCalled()
  })

  it('sets inline _general error for SUBSCRIPTION_CANCELLED', async () => {
    mockCreateAppointment.mockRejectedValue(makeError('SUBSCRIPTION_CANCELLED'))
    await submitForm()
    expect(mockSetError).toHaveBeenCalledWith('_general', 'appointments.error.subscriptionCancelled')
    expect(mockToastError).not.toHaveBeenCalled()
  })

  it('shows generic toast for unknown errors', async () => {
    mockCreateAppointment.mockRejectedValue(makeError('SOME_UNKNOWN_ERROR'))
    await submitForm()
    expect(mockToastError).toHaveBeenCalledWith('appointments.toast.createError')
    expect(mockSetError).not.toHaveBeenCalled()
  })
})
