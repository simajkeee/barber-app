import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import FacebookLoginButton from '~/components/auth/FacebookLoginButton.vue'
import { UiButtonStub, UiAlertStub } from '../../stubs'

const mockFacebookLogin = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  facebookLogin: mockFacebookLogin,
}))

vi.stubGlobal('useRuntimeConfig', () => ({
  public: { facebookAppId: 'test-app-id' },
}))

function createMockFB(accessToken: string | null = 'fb-token-123') {
  return {
    init: vi.fn(),
    login: vi.fn((callback: Function) => {
      if (accessToken) {
        callback({ authResponse: { accessToken } })
      } else {
        callback({})
      }
    }),
  }
}

describe('FacebookLoginButton', () => {
  beforeEach(() => {
    mockFacebookLogin.mockReset()
    delete (window as any).FB
    delete (window as any).fbAsyncInit
  })

  function mountButton() {
    return mount(FacebookLoginButton, {
      global: {
        stubs: {
          UiButton: UiButtonStub,
          UiAlert: UiAlertStub,
        },
      },
    })
  }

  it('renders Facebook button with i18n text', () => {
    const wrapper = mountButton()
    expect(wrapper.find('button').text()).toContain('auth.facebook.button')
  })

  it('emits success with isNewUser payload on successful Facebook login', async () => {
    ;(window as any).FB = createMockFB()
    mockFacebookLogin.mockResolvedValueOnce({ success: true, isNewUser: true })

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(mockFacebookLogin).toHaveBeenCalledWith('fb-token-123')
    expect(wrapper.emitted('success')).toHaveLength(1)
    expect(wrapper.emitted('success')![0]).toEqual([{ isNewUser: true }])
  })

  it('emits success with isNewUser false for returning users', async () => {
    ;(window as any).FB = createMockFB()
    mockFacebookLogin.mockResolvedValueOnce({ success: true, isNewUser: false })

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.emitted('success')![0]).toEqual([{ isNewUser: false }])
  })

  it('shows error when Facebook login returns failure result', async () => {
    ;(window as any).FB = createMockFB()
    mockFacebookLogin.mockResolvedValueOnce({ success: false, error: 'FACEBOOK_AUTH_FAILED' })

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert').exists()).toBe(true)
    expect(wrapper.find('.alert').text()).toContain('auth.error.facebookFailed')
  })

  it('shows error when user cancels Facebook dialog', async () => {
    ;(window as any).FB = createMockFB(null)

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert').text()).toContain('auth.error.facebookFailed')
    expect(mockFacebookLogin).not.toHaveBeenCalled()
  })

  it('shows error when facebookLogin throws', async () => {
    ;(window as any).FB = createMockFB()
    mockFacebookLogin.mockRejectedValueOnce(new Error('Network error'))

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert').text()).toContain('auth.error.facebookFailed')
  })

  it('loads Facebook SDK if not already present', async () => {
    mockFacebookLogin.mockResolvedValueOnce({ success: true })

    // Intercept appendChild to prevent happy-dom from loading the external script
    let appendedScript: HTMLScriptElement | null = null
    const originalAppendChild = document.head.appendChild.bind(document.head)
    vi.spyOn(document.head, 'appendChild').mockImplementation((node: any) => {
      if (node.tagName === 'SCRIPT' && node.src?.includes('facebook')) {
        appendedScript = node
        return node
      }
      return originalAppendChild(node)
    })

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')

    // SDK script should have been intercepted
    expect(appendedScript).not.toBeNull()
    expect(appendedScript!.src).toContain('facebook')

    // Simulate SDK load by calling fbAsyncInit
    ;(window as any).FB = createMockFB()
    ;(window as any).fbAsyncInit()
    await flushPromises()

    expect(window.FB.init).toHaveBeenCalledWith(
      expect.objectContaining({ appId: 'test-app-id' }),
    )
    expect(mockFacebookLogin).toHaveBeenCalledWith('fb-token-123')

    vi.restoreAllMocks()
  })

  it('does not show error initially', () => {
    const wrapper = mountButton()
    expect(wrapper.find('.alert').exists()).toBe(false)
  })

  it('clears previous error on new click', async () => {
    ;(window as any).FB = createMockFB(null)

    const wrapper = mountButton()
    await wrapper.find('button').trigger('click')
    await flushPromises()
    expect(wrapper.find('.alert').exists()).toBe(true)

    // Second click: FB now succeeds
    ;(window as any).FB = createMockFB()
    mockFacebookLogin.mockResolvedValueOnce({ success: true })
    await wrapper.find('button').trigger('click')
    await flushPromises()
    expect(wrapper.find('.alert').exists()).toBe(false)
  })
})