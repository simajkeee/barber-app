import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import LoginForm from '~/components/auth/LoginForm.vue'
import { authFormStubs } from '../../stubs'

const mockLogin = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  login: mockLogin,
}))

async function flush() {
  await flushPromises()
  await nextTick()
  await flushPromises()
}

describe('LoginForm', () => {
  beforeEach(() => {
    mockLogin.mockReset()
  })

  function mountForm() {
    return mount(LoginForm, {
      global: { stubs: authFormStubs },
    })
  }

  async function submitForm(wrapper: ReturnType<typeof mountForm>) {
    await (wrapper.vm as any).onSubmit()
    await flush()
  }

  async function fillAndSubmit(wrapper: ReturnType<typeof mountForm>, email = 'test@example.com', password = 'password123') {
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue(email)
    await inputs[1].setValue(password)
    await submitForm(wrapper)
  }

  it('renders two input fields', () => {
    const wrapper = mountForm()
    expect(wrapper.findAll('.ui-input')).toHaveLength(2)
  })

  it('validates empty fields on submit', async () => {
    const wrapper = mountForm()
    await submitForm(wrapper)
    expect(mockLogin).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBe(2)
  })

  it('validates email format', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('not-an-email')
    await inputs[1].setValue('password123')
    await submitForm(wrapper)
    expect(mockLogin).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.emailInvalid'))).toBe(true)
  })

  it('validates password required', async () => {
    const wrapper = mountForm()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('test@example.com')
    await submitForm(wrapper)
    expect(mockLogin).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.'))).toBe(true)
  })

  it('calls login with form data on valid submit', async () => {
    mockLogin.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(mockLogin).toHaveBeenCalledWith({
      email: 'test@example.com',
      password: 'password123',
    })
  })

  it('emits success on successful login', async () => {
    mockLogin.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.emitted('success')).toHaveLength(1)
  })

  it('shows INVALID_CREDENTIALS error as general alert', async () => {
    mockLogin.mockResolvedValueOnce({ success: false, error: 'INVALID_CREDENTIALS' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('auth.error.invalidCredentials')
  })

  it('shows field errors from API response', async () => {
    mockLogin.mockResolvedValueOnce({
      success: false,
      error: 'VALIDATION_ERROR',
      fieldErrors: { email: 'Already registered' },
    })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.findAll('.error').some(e => e.text() === 'Already registered')).toBe(true)
  })

  it('shows generic error from API', async () => {
    mockLogin.mockResolvedValueOnce({ success: false, error: 'UNKNOWN_ERROR' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('UNKNOWN_ERROR')
  })

  it('does not emit success on failed login', async () => {
    mockLogin.mockResolvedValueOnce({ success: false, error: 'INVALID_CREDENTIALS' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.emitted('success')).toBeUndefined()
  })
})