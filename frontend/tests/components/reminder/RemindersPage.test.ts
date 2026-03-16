import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import RemindersPage from '~/pages/dashboard/reminders/index.vue'
import { createReminderTodayResponse, createReminderCandidate } from '~/tests/factories'

const stubs = {
  DashboardPageHeader: { template: '<div><slot name="actions" /></div>' },
  ReminderSettingsSummary: true,
  ReminderList: true,
  ReminderListPagination: true,
  NuxtLink: { template: '<a><slot /></a>' },
  UiButton: { template: '<button><slot /></button>' },
}

describe('RemindersPage', () => {
  let tSpy: ReturnType<typeof vi.fn>

  beforeEach(() => {
    tSpy = vi.fn((key: string) => key)
    vi.stubGlobal('useI18n', () => ({ t: tSpy, locale: { value: 'en' } }))
  })

  function mountPage(total: number) {
    const candidates = Array.from({ length: total }, (_, i) =>
      createReminderCandidate({ clientId: `client-${i}` }),
    )
    const getTodayReminders = vi.fn().mockResolvedValue(
      createReminderTodayResponse(candidates),
    )
    vi.stubGlobal('useReminderApi', () => ({ getTodayReminders, markReminded: vi.fn() }))

    return mount(RemindersPage, { global: { stubs } })
  }

  it('calls t with count as a number for singular (1 client)', async () => {
    mountPage(1)
    await flushPromises()

    const call = tSpy.mock.calls.find(([key]) => key === 'reminders.totalCount')
    expect(call).toBeDefined()
    expect(call![1]).toBe(1)
  })

  it('calls t with count as a number for plural (3 clients)', async () => {
    mountPage(3)
    await flushPromises()

    const call = tSpy.mock.calls.find(([key]) => key === 'reminders.totalCount')
    expect(call).toBeDefined()
    expect(call![1]).toBe(3)
  })

  it('does not call totalCount when list is empty', async () => {
    mountPage(0)
    await flushPromises()

    const call = tSpy.mock.calls.find(([key]) => key === 'reminders.totalCount')
    expect(call).toBeUndefined()
  })
})