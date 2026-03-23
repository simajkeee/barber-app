import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { defineComponent } from 'vue'
import RegisterPage from '~/pages/register.vue'

const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

const AuthRegisterFormStub = defineComponent({
  name: 'AuthRegisterForm',
  emits: ['success'],
  template: '<div class="register-form" />',
})

const AuthFacebookLoginButtonStub = defineComponent({
  name: 'AuthFacebookLoginButton',
  emits: ['success'],
  template: '<div class="fb-button" />',
})

describe('RegisterPage', () => {
  beforeEach(() => {
    mockNavigateTo.mockReset()
  })

  function mountPage() {
    return mount(RegisterPage, {
      global: {
        stubs: {
          AuthPageHeader: { template: '<div />', props: ['title'] },
          AuthRegisterForm: AuthRegisterFormStub,
          AuthFacebookLoginButton: AuthFacebookLoginButtonStub,
          AuthDivider: { template: '<div />' },
          AuthFooterLink: { template: '<div />', props: ['text', 'linkText', 'to'] },
        },
      },
    })
  }

  it('navigates to /dashboard/shop/create on email registration success', async () => {
    const wrapper = mountPage()
    const form = wrapper.findComponent(AuthRegisterFormStub)
    form.vm.$emit('success')
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard/shop/create')
  })

  it('navigates to /dashboard/shop/create on Facebook login (new user)', async () => {
    const wrapper = mountPage()
    const fb = wrapper.findComponent(AuthFacebookLoginButtonStub)
    fb.vm.$emit('success', { isNewUser: true })
    await flushPromises()

    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard/shop/create')
  })

  it('navigates to /dashboard/shop/create on Facebook login (existing user on register page)', async () => {
    const wrapper = mountPage()
    const fb = wrapper.findComponent(AuthFacebookLoginButtonStub)
    fb.vm.$emit('success', { isNewUser: false })
    await flushPromises()

    // Register page always redirects to shop create regardless of isNewUser
    expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard/shop/create')
  })
})
