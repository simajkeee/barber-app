import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import RegisterForm from '~/components/auth/RegisterForm.vue'
import { authFormStubs } from '../../stubs'

const mockRegister = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  register: mockRegister,
}))

async function flush() {
  await flushPromises()
  await nextTick()
  await flushPromises()
}

describe('RegisterForm', () => {
  beforeEach(() => {
    mockRegister.mockReset()
  })

  function mountForm() {
    return mount(RegisterForm, {
      global: { stubs: authFormStubs },
    })
  }

  async function submitForm(wrapper: ReturnType<typeof mountForm>) {
    await (wrapper.vm as any).onSubmit()
    await flush()
  }

  async function fillValidForm(wrapper: ReturnType<typeof mountForm>) {
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('John')
    await inputs[1].setValue('Doe')
    await inputs[2].setValue('test@example.com')
    await inputs[3].setValue('password123')
  }

  it('renders four input fields', () => {
    const wrapper = mountForm()
    expect(wrapper.findAll('.ui-input')).toHaveLength(4)
  })

  it('validates all empty fields on submit', async () => {
    const wrapper = mountForm()
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBe(4)
  })

  it('validates password minimum length', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('John')
    await inputs[1].setValue('Doe')
    await inputs[2].setValue('test@example.com')
    await inputs[3].setValue('short')
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.minLength'))).toBe(true)
  })

  it('validates email format', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('John')
    await inputs[1].setValue('Doe')
    await inputs[2].setValue('not-an-email')
    await inputs[3].setValue('password123')
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.emailInvalid'))).toBe(true)
  })

  it('calls register with form data and locale on valid submit', async () => {
    mockRegister.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillValidForm(wrapper)
    await submitForm(wrapper)
    expect(mockRegister).toHaveBeenCalledWith({
      firstName: 'John',
      lastName: 'Doe',
      email: 'test@example.com',
      password: 'password123',
      locale: 'vi',
    })
  })

  it('emits success on successful registration', async () => {
    mockRegister.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillValidForm(wrapper)
    await submitForm(wrapper)
    expect(wrapper.emitted('success')).toHaveLength(1)
  })

  it('shows EMAIL_ALREADY_EXISTS error as general alert', async () => {
    mockRegister.mockResolvedValueOnce({ success: false, error: 'EMAIL_ALREADY_EXISTS' })
    const wrapper = mountForm()
    await fillValidForm(wrapper)
    await submitForm(wrapper)
    expect(wrapper.find('.alert').text()).toContain('auth.error.emailExists')
  })

  it('shows field errors from API response', async () => {
    mockRegister.mockResolvedValueOnce({
      success: false,
      error: 'VALIDATION_ERROR',
      fieldErrors: { email: 'Already registered' },
    })
    const wrapper = mountForm()
    await fillValidForm(wrapper)
    await submitForm(wrapper)
    expect(wrapper.findAll('.error').some(e => e.text() === 'Already registered')).toBe(true)
  })
})