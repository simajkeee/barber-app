import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import RegisterForm from '~/components/auth/RegisterForm.vue'
import { authFormStubs } from '../../stubs'
import { flush } from '../../utils'

const mockRegister = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  register: mockRegister,
}))

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
    await inputs[0].setValue('John')       // firstName
    await inputs[1].setValue('Doe')        // lastName
    await inputs[2].setValue('test@example.com') // email
    await inputs[3].setValue('password123') // password
    await inputs[4].setValue('password123') // confirmPassword
    await inputs[5].setValue('0901234567') // phoneNumber
  }

  it('renders six input fields', () => {
    const wrapper = mountForm()
    expect(wrapper.findAll('.ui-input')).toHaveLength(6)
  })

  it('validates all empty fields on submit', async () => {
    const wrapper = mountForm()
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBeGreaterThanOrEqual(5)
  })

  it('validates password minimum length', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('John')
    await inputs[1].setValue('Doe')
    await inputs[2].setValue('test@example.com')
    await inputs[3].setValue('short')
    await inputs[4].setValue('short')
    await inputs[5].setValue('0901234567')
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
    await inputs[4].setValue('password123')
    await inputs[5].setValue('0901234567')
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.emailInvalid'))).toBe(true)
  })

  it('shows error when passwords do not match', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('John')
    await inputs[1].setValue('Doe')
    await inputs[2].setValue('test@example.com')
    await inputs[3].setValue('password123')
    await inputs[4].setValue('different999')
    await inputs[5].setValue('0901234567')
    await submitForm(wrapper)
    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.passwordMismatch'))).toBe(true)
  })

  it('calls register without confirmPassword when passwords match', async () => {
    mockRegister.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillValidForm(wrapper)
    await submitForm(wrapper)
    expect(mockRegister).toHaveBeenCalledWith({
      firstName: 'John',
      lastName: 'Doe',
      email: 'test@example.com',
      password: 'password123',
      phoneNumber: '0901234567',
      locale: 'vi',
    })
    expect(mockRegister).not.toHaveBeenCalledWith(expect.objectContaining({ confirmPassword: expect.anything() }))
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
