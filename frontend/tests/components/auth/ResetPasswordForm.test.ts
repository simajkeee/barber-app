import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ResetPasswordForm from '~/components/auth/ResetPasswordForm.vue'
import { authFormStubs } from '../../stubs'
import { flush } from '../../utils'

const mockResetPassword = vi.fn()
const mockNavigateTo = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  resetPassword: mockResetPassword,
}))

vi.stubGlobal('navigateTo', mockNavigateTo)
vi.stubGlobal('useLocalePath', () => (path: string) => path)

describe('ResetPasswordForm', () => {
  beforeEach(() => {
    mockResetPassword.mockReset()
    mockNavigateTo.mockReset()
  })

  function mountForm(token = 'valid-token-123') {
    return mount(ResetPasswordForm, {
      props: { token },
      global: { stubs: authFormStubs },
    })
  }

  async function submitForm(wrapper: ReturnType<typeof mountForm>) {
    await (wrapper.vm as any).onSubmit()
    await flush()
  }

  async function fillAndSubmit(wrapper: ReturnType<typeof mountForm>, password = 'newPassword123') {
    const input = wrapper.find('input')
    await input.setValue(password)
    await submitForm(wrapper)
  }

  it('renders one password input field', () => {
    const wrapper = mountForm()
    expect(wrapper.findAll('.ui-input')).toHaveLength(1)
  })

  it('validates empty password on submit', async () => {
    const wrapper = mountForm()
    await submitForm(wrapper)
    expect(mockResetPassword).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBe(1)
  })

  it('validates password minimum length', async () => {
    const wrapper = mountForm()
    const input = wrapper.find('input')
    await input.setValue('short')
    await submitForm(wrapper)
    expect(mockResetPassword).not.toHaveBeenCalled()
    expect(wrapper.findAll('.error').length).toBe(1)
  })

  it('calls resetPassword with token and password on valid submit', async () => {
    mockResetPassword.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm('my-token')
    await fillAndSubmit(wrapper)
    expect(mockResetPassword).toHaveBeenCalledWith('my-token', 'newPassword123')
  })

  it('navigates to login with resetSuccess on success', async () => {
    mockResetPassword.mockResolvedValueOnce({ success: true })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(mockNavigateTo).toHaveBeenCalledWith({
      path: '/login',
      query: { resetSuccess: 'true' },
    })
  })

  it('shows invalid token error on INVALID_RESET_TOKEN', async () => {
    mockResetPassword.mockResolvedValueOnce({ success: false, error: 'INVALID_RESET_TOKEN' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('auth.error.invalidResetToken')
  })

  it('shows field errors from API response', async () => {
    mockResetPassword.mockResolvedValueOnce({
      success: false,
      error: 'VALIDATION_ERROR',
      fieldErrors: { password: 'Password too common' },
    })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.findAll('.error').some(e => e.text() === 'Password too common')).toBe(true)
  })

  it('shows generic error from API', async () => {
    mockResetPassword.mockResolvedValueOnce({ success: false, error: 'SERVER_ERROR' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(wrapper.find('.alert').text()).toContain('SERVER_ERROR')
  })

  it('does not navigate on failed submit', async () => {
    mockResetPassword.mockResolvedValueOnce({ success: false, error: 'INVALID_RESET_TOKEN' })
    const wrapper = mountForm()
    await fillAndSubmit(wrapper)
    expect(mockNavigateTo).not.toHaveBeenCalled()
  })
})