import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ForgotPasswordForm from '~/components/auth/ForgotPasswordForm.vue'
import { authFormStubs } from '../../stubs'
import { flush } from '../../utils'

const mockForgotPassword = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  forgotPassword: mockForgotPassword,
}))

describe('ForgotPasswordForm', () => {
  beforeEach(() => {
    mockForgotPassword.mockReset()
  })

  function mountForm() {
    return mount(ForgotPasswordForm, {
      global: { stubs: authFormStubs },
    })
  }

  async function submitForm(wrapper: ReturnType<typeof mountForm>) {
    await (wrapper.vm as any).onSubmit()
    await flush()
  }

  async function fillAndSubmit(wrapper: ReturnType<typeof mountForm>, email = 'test@example.com') {
    const input = wrapper.find('input')
    await input.setValue(email)
    await submitForm(wrapper)
  }

  it('renders one email input field', () => {
    const wrapper = mountForm()
    expect(wrapper.findAll('.ui-input')).toHaveLength(1)
  })

  it('renders description text', () => {
    const wrapper = mountForm()
    expect(wrapper.text()).toContain('auth.forgotPassword.description')
  })

  it('validates empty email on submit', async () => {
    const wrapper = mountForm()
    await submitForm(wrapper)
    expect(mockForgotPassword).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBe(1)
  })

  it('validates email format', async () => {
    const wrapper = mountForm()
    const input = wrapper.find('input')
    await input.setValue('not-an-email')
    await submitForm(wrapper)
    expect(mockForgotPassword).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').some(e => e.text().includes('validation.emailInvalid'))).toBe(true)
  })

  it('calls forgotPassword with email on valid submit', async () => {
    mockForgotPassword.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(mockForgotPassword).toHaveBeenCalledWith('test@example.com')
  })

  it('shows success message and hides form on success', async () => {
    mockForgotPassword.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('auth.forgotPassword.success')
    expect(wrapper.find('form').exists()).toBe(false)
  })

  it('shows rate limit error on RATE_LIMIT_EXCEEDED', async () => {
    mockForgotPassword.mockResolvedValueOnce({ success: false, error: 'RATE_LIMIT_EXCEEDED' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('auth.error.rateLimitExceeded')
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('shows generic error from API', async () => {
    mockForgotPassword.mockResolvedValueOnce({ success: false, error: 'UNKNOWN_ERROR' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('UNKNOWN_ERROR')
  })

  it('does not show success on failed submit', async () => {
    mockForgotPassword.mockResolvedValueOnce({ success: false, error: 'RATE_LIMIT_EXCEEDED' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.text()).not.toContain('auth.forgotPassword.success')
  })
})