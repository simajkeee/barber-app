import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createAppointment, createAppointmentService, createShopService } from '../../factories'

// Must be set up before importing the component so the component's setup() sees the mocks
const mockListClients = vi.fn()
const mockFetchServices = vi.fn()
const mockGetAvailableSlots = vi.fn()

vi.stubGlobal('useClientApi', () => ({ listClients: mockListClients }))
vi.stubGlobal('useShopApi', () => ({ fetchServices: mockFetchServices }))
vi.stubGlobal('useAppointmentApi', () => ({ getAvailableSlots: mockGetAvailableSlots }))

const { default: Form } = await import('~/components/appointment/Form.vue')

const formStubs = {
  AppointmentClientSearchSelect: {
    template: '<div><input class="client-input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value || null)" /></div>',
    props: ['modelValue', 'clients', 'error'],
    emits: ['update:modelValue'],
  },
  AppointmentSlotPicker: {
    template: '<div class="slot-picker"><button class="slot-btn" @click="$emit(\'update:modelValue\', \'2026-03-15T02:00:00.000Z\')">09:00</button></div>',
    props: ['slots', 'modelValue', 'isLoading', 'error'],
    emits: ['update:modelValue'],
  },
  UiTextarea: {
    template: '<textarea class="notes-input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    props: ['modelValue', 'label', 'rows', 'error', 'placeholder'],
    emits: ['update:modelValue'],
  },
  UiButton: {
    template: '<button :type="type || \'button\'" :disabled="loading"><slot /></button>',
    props: ['variant', 'loading', 'type'],
  },
  UiAlert: {
    template: '<div role="alert">{{ message }}</div>',
    props: ['message'],
  },
}

function mountForm(props: Record<string, any> = {}) {
  return mount(Form, {
    props,
    global: { stubs: formStubs },
  })
}

const defaultService = createShopService({ id: 'service-1' })

describe('AppointmentForm', () => {
  beforeEach(() => {
    mockListClients.mockReset()
    mockFetchServices.mockReset()
    mockGetAvailableSlots.mockReset()
    mockListClients.mockResolvedValue({ data: [], pagination: { nextCursor: null, hasMore: false } })
    mockFetchServices.mockResolvedValue({ services: [defaultService] })
    mockGetAvailableSlots.mockResolvedValue({ slots: [] })
  })

  describe('create mode (no appointment prop)', () => {
    it('renders client search, service select, date input and notes', async () => {
      const wrapper = mountForm()
      await flushPromises()
      expect(wrapper.find('.client-input').exists()).toBe(true)
      expect(wrapper.find('select').exists()).toBe(true)
      expect(wrapper.find('input[type="date"]').exists()).toBe(true)
      expect(wrapper.find('.notes-input').exists()).toBe(true)
    })

    it('fetches clients and services on mount', async () => {
      mountForm()
      await flushPromises()
      expect(mockListClients).toHaveBeenCalledWith({ limit: 200 })
      expect(mockFetchServices).toHaveBeenCalledWith(false)
    })

    it('does not emit submit when required fields are empty', async () => {
      const wrapper = mountForm()
      await flushPromises()
      await wrapper.find('form').trigger('submit')
      await flushPromises()
      expect(wrapper.emitted('submit')).toBeUndefined()
    })

    it('renders save and cancel buttons', async () => {
      const wrapper = mountForm()
      await flushPromises()
      const text = wrapper.text()
      expect(text).toContain('common.save')
      expect(text).toContain('common.cancel')
    })
  })

  describe('edit mode (with appointment prop)', () => {
    it('pre-fills service select with appointment service id when service is loaded', async () => {
      const service = createAppointmentService({ id: 'svc-edit', name: 'Beard Trim' })
      mockFetchServices.mockResolvedValueOnce({ services: [createShopService({ id: 'svc-edit', name: 'Beard Trim' })] })
      const appointment = createAppointment({ service })
      const wrapper = mountForm({ appointment })
      await flushPromises()
      const select = wrapper.find('select')
      expect((select.element as HTMLSelectElement).value).toBe('svc-edit')
    })

    it('pre-fills date input with appointment date', async () => {
      const appointment = createAppointment({ startTime: '2026-03-20T02:00:00.000Z' })
      const wrapper = mountForm({ appointment })
      await flushPromises()
      const dateInput = wrapper.find('input[type="date"]')
      expect((dateInput.element as HTMLInputElement).value).toBe('2026-03-20')
    })
  })

  describe('cancel', () => {
    it('emits cancel when cancel button clicked', async () => {
      const wrapper = mountForm()
      await flushPromises()
      const cancelBtn = wrapper.findAll('button').find((b) => b.text().includes('common.cancel'))!
      await cancelBtn.trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })
  })

  describe('loading state', () => {
    it('disables submit button when loading prop is true', async () => {
      const wrapper = mountForm({ loading: true })
      await flushPromises()
      const submitBtn = wrapper.findAll('button').find((b) => b.attributes('type') === 'submit')!
      expect(submitBtn.attributes('disabled')).toBeDefined()
    })
  })

  describe('setError', () => {
    it('exposes setError method', async () => {
      const wrapper = mountForm()
      await flushPromises()
      expect(typeof (wrapper.vm as any).setError).toBe('function')
    })

    it('shows general error via setError(_general, message)', async () => {
      const wrapper = mountForm()
      await flushPromises()
      ;(wrapper.vm as any).setError('_general', 'Overlap detected')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="alert"]').text()).toContain('Overlap detected')
    })
  })
})
