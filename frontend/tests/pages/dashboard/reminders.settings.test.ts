import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createReminderSettings } from '../../factories'

const mockGetSettings = vi.fn()
const mockUpdateSettings = vi.fn()

vi.stubGlobal('useReminderApi', () => ({
  getSettings: mockGetSettings,
  updateSettings: mockUpdateSettings,
}))

const { default: SettingsPage } = await import('~/pages/dashboard/reminders/settings.vue')

const pageStubs = {
  DashboardPageHeader: {
    template: '<div><slot name="actions" /></div>',
    props: ['title'],
  },
  ReminderSettingsForm: {
    template: '<form class="settings-form" @submit.prevent />',
    props: ['settings', 'isLoading'],
    emits: ['save'],
  },
  NuxtLink: { template: '<a><slot /></a>', props: ['to'] },
  UiButton: { template: '<button><slot /></button>', props: ['variant'] },
}

describe('RemindersSettingsPage', () => {
  const mockToast = { success: vi.fn(), error: vi.fn() }

  beforeEach(() => {
    mockGetSettings.mockReset()
    mockUpdateSettings.mockReset()
    mockToast.success.mockReset()
    mockToast.error.mockReset()
    vi.stubGlobal('useToast', () => mockToast)
  })

  function mountPage() {
    return mount(SettingsPage, { global: { stubs: pageStubs } })
  }

  it('calls getSettings on mount', async () => {
    mockGetSettings.mockResolvedValue(createReminderSettings())
    mountPage()
    await flushPromises()
    expect(mockGetSettings).toHaveBeenCalledOnce()
  })

  it('shows loading skeleton before settings load', () => {
    mockGetSettings.mockReturnValue(new Promise(() => {}))
    const wrapper = mountPage()
    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
    expect(wrapper.find('.settings-form').exists()).toBe(false)
  })

  it('shows form after settings load', async () => {
    mockGetSettings.mockResolvedValue(createReminderSettings())
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('.animate-pulse').exists()).toBe(false)
    expect(wrapper.find('.settings-form').exists()).toBe(true)
  })

  it('calls updateSettings and navigates on save', async () => {
    const settings = createReminderSettings()
    mockGetSettings.mockResolvedValue(settings)
    mockUpdateSettings.mockResolvedValue(createReminderSettings({ daysSinceLastVisit: 14 }))

    const wrapper = mountPage()
    await flushPromises()

    // Trigger save via emitting from the stub
    const formEl = wrapper.findComponent(pageStubs.ReminderSettingsForm)
    await formEl.vm.$emit('save', { daysSinceLastVisit: 14 })
    await flushPromises()

    expect(mockUpdateSettings).toHaveBeenCalledWith({ daysSinceLastVisit: 14, locale: 'vi' })
    expect(navigateTo).toHaveBeenCalled()
  })

  it('shows success toast when settings saved', async () => {
    mockGetSettings.mockResolvedValue(createReminderSettings())
    mockUpdateSettings.mockResolvedValue(createReminderSettings())

    const wrapper = mountPage()
    await flushPromises()

    const formEl = wrapper.findComponent(pageStubs.ReminderSettingsForm)
    await formEl.vm.$emit('save', { daysSinceLastVisit: 14 })
    await flushPromises()

    expect(mockToast.success).toHaveBeenCalledWith('reminders.toast.settingsSaved')
  })

  it('shows error toast when getSettings fails', async () => {
    mockGetSettings.mockRejectedValue(new Error('Network error'))
    mountPage()
    await flushPromises()
    expect(mockToast.error).toHaveBeenCalledWith('reminders.toast.settingsError')
  })

  it('shows error toast when updateSettings fails', async () => {
    mockGetSettings.mockResolvedValue(createReminderSettings())
    mockUpdateSettings.mockRejectedValue(new Error('Save failed'))

    const wrapper = mountPage()
    await flushPromises()

    const formEl = wrapper.findComponent(pageStubs.ReminderSettingsForm)
    await formEl.vm.$emit('save', { daysSinceLastVisit: 14 })
    await flushPromises()

    expect(mockToast.error).toHaveBeenCalledWith('reminders.toast.settingsError')
  })
})
