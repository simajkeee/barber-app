import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { defineComponent } from 'vue'
import LoginPage from '~/pages/login.vue'

const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

const AuthLoginFormStub = defineComponent({
  name: 'AuthLoginForm',
  emits: ['success'],
  template: '<div class="login-form" />',
})

const AuthFacebookLoginButtonStub = defineComponent({
  name: 'AuthFacebookLoginButton',
  emits: ['success'],
  template: '<div class="fb-button" />',
})

describe('LoginPage', () => {
  beforeEach(() => {
    mockNavigateTo.mockReset()
  })

  function mountPage() {
    return mount(LoginPage, {
      global: {
        stubs: {
          AuthPageHeader: { template: '<div />', props: ['title'] },
          AuthLoginForm: AuthLoginFormStub,
          AuthFacebookLoginButton: AuthFacebookLoginButtonStub,
          AuthDivider: { template: '<div />' },
          AuthFooterLink: { template: '<div />', props: ['text', 'linkText', 'to'] },
          UiAlert: { template: '<div />', props: ['message', 'type'] },
        },
      },
    })
  }

  it('navigates to /dashboard on email login success', async () => {
    const wrapper = mountPage()
    const form = wrapper.findComponent(AuthLoginFormStub)
    form.vm.$emit('success')
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard')
  })

  it('navigates to /dashboard on Facebook login with no payload', async () => {
    const wrapper = mountPage()
    const fb = wrapper.findComponent(AuthFacebookLoginButtonStub)
    fb.vm.$emit('success')
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard')
  })

  it('navigates to /dashboard/shop/create on Facebook login with isNewUser true', async () => {
    const wrapper = mountPage()
    const fb = wrapper.findComponent(AuthFacebookLoginButtonStub)
    fb.vm.$emit('success', { isNewUser: true })
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard/shop/create')
  })

  it('navigates to /dashboard on Facebook login with isNewUser false', async () => {
    const wrapper = mountPage()
    const fb = wrapper.findComponent(AuthFacebookLoginButtonStub)
    fb.vm.$emit('success', { isNewUser: false })
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard')
  })
})
