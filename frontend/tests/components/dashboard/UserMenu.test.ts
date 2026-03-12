import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'
import UserMenu from '~/components/dashboard/UserMenu.vue'
import { createUser } from '../../factories'

const mockLogout = vi.fn()

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('vi'),
}))

vi.stubGlobal('useAuth', () => ({
  logout: mockLogout,
}))

const mockApiFetch = vi.fn()
vi.stubGlobal('useApi', () => mockApiFetch)

const { useAuthStore: realUseAuthStore } = await import('~/stores/auth')
vi.stubGlobal('useAuthStore', realUseAuthStore)

describe('UserMenu', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockLogout.mockReset()
  })

  function mountWithUser(overrides = {}) {
    const store = realUseAuthStore()
    store.setUser(createUser(overrides))
    return mount(UserMenu)
  }

  it('renders user initials', () => {
    const wrapper = mountWithUser({ firstName: 'Jane', lastName: 'Smith' })
    expect(wrapper.text()).toContain('JS')
  })

  it('renders full name', () => {
    const wrapper = mountWithUser({ firstName: 'Jane', lastName: 'Smith' })
    expect(wrapper.text()).toContain('Jane Smith')
  })

  it('renders logout button with i18n key', () => {
    const wrapper = mountWithUser()
    expect(wrapper.find('button').text()).toContain('auth.logout')
  })

  it('calls logout on button click', async () => {
    mockLogout.mockResolvedValueOnce(undefined)
    const wrapper = mountWithUser()
    await wrapper.find('button').trigger('click')
    await flushPromises()
    expect(mockLogout).toHaveBeenCalledOnce()
  })

  it('disables button during logout', async () => {
    let resolveLogout: Function
    mockLogout.mockReturnValueOnce(new Promise(r => { resolveLogout = r }))

    const wrapper = mountWithUser()
    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('button').attributes('disabled')).toBeDefined()

    resolveLogout!()
    await flushPromises()
  })
})