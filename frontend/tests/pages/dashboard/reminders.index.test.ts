import type { ReminderCandidate } from '~/types/reminder'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { defineComponent } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createReminderTodayResponse, createReminderCandidate } from '../../factories'

const mockGetTodayReminders = vi.fn()
const mockMarkReminded = vi.fn()

vi.stubGlobal('useReminderApi', () => ({
  getTodayReminders: mockGetTodayReminders,
  markReminded: mockMarkReminded,
}))

const { default: RemindersPage } = await import('~/pages/dashboard/reminders/index.vue')

const ReminderListStub = {
  template: '<div class="reminder-list" />',
  props: ['candidates', 'isLoading'],
  emits: ['markReminded'],
}

const ReminderListPaginationStub = {
  template: '<div class="pagination" />',
  props: ['hasMore', 'isLoading'],
  emits: ['loadMore'],
}

const pageStubs = {
  DashboardPageHeader: {
    template: '<div><slot name="actions" /></div>',
    props: ['title'],
  },
  ReminderSettingsSummary: { template: '<div class="settings-summary" />', props: ['settings'] },
  ReminderList: ReminderListStub,
  ReminderListPagination: ReminderListPaginationStub,
  NuxtLink: { template: '<a><slot /></a>', props: ['to'] },
  UiButton: { template: '<button><slot /></button>', props: ['variant'] },
}

describe('RemindersPage', () => {
  const mockToast = { success: vi.fn(), error: vi.fn() }

  beforeEach(() => {
    mockGetTodayReminders.mockReset()
    mockMarkReminded.mockReset()
    mockToast.success.mockReset()
    mockToast.error.mockReset()
    vi.stubGlobal('useToast', () => mockToast)
  })

  function mountPage() {
    return mount(
      defineComponent({
        components: { RemindersPage },
        template: '<Suspense><RemindersPage /></Suspense>',
      }),
      { global: { stubs: pageStubs } },
    )
  }

  it('calls getTodayReminders on mount', async () => {
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse())
    mountPage()
    await flushPromises()
    expect(mockGetTodayReminders).toHaveBeenCalledOnce()
  })

  it('passes candidates to ReminderList', async () => {
    const candidates = [createReminderCandidate({ clientId: 'c1' }), createReminderCandidate({ clientId: 'c2' })]
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse(candidates))
    const wrapper = mountPage()
    await flushPromises()
    const list = wrapper.findComponent(ReminderListStub)
    expect(list.props('candidates')).toHaveLength(2)
  })

  it('shows settings summary after load', async () => {
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse())
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('.settings-summary').exists()).toBe(true)
  })

  it('shows pagination when cursor is non-null', async () => {
    mockGetTodayReminders.mockResolvedValue(
      createReminderTodayResponse([], { meta: { total: 0, cursor: 'next-cursor' } }),
    )
    const wrapper = mountPage()
    await flushPromises()
    const pagination = wrapper.findComponent(ReminderListPaginationStub)
    expect(pagination.props('hasMore')).toBe(true)
  })

  it('does not show pagination when cursor is null', async () => {
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse())
    const wrapper = mountPage()
    await flushPromises()
    const pagination = wrapper.findComponent(ReminderListPaginationStub)
    expect(pagination.props('hasMore')).toBe(false)
  })

  it('removes candidate from list after markReminded', async () => {
    const candidates = [
      createReminderCandidate({ clientId: 'c1' }),
      createReminderCandidate({ clientId: 'c2' }),
    ]
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse(candidates))
    mockMarkReminded.mockResolvedValue({ clientId: 'c1', lastRemindedAt: '2026-03-16T10:00:00Z' })

    const wrapper = mountPage()
    await flushPromises()

    const list = wrapper.findComponent(ReminderListStub)
    await list.vm.$emit('markReminded', 'c1')
    await flushPromises()

    expect(list.props('candidates')).toHaveLength(1)
    expect(list.props('candidates')[0].clientId).toBe('c2')
  })

  it('shows load error toast when fetch fails', async () => {
    mockGetTodayReminders.mockRejectedValue(new Error('Network error'))
    mountPage()
    await flushPromises()
    expect(mockToast.error).toHaveBeenCalledWith('reminders.toast.loadError')
  })

  it('shows mark error toast when markReminded fails', async () => {
    mockGetTodayReminders.mockResolvedValue(createReminderTodayResponse([createReminderCandidate()]))
    mockMarkReminded.mockRejectedValue(new Error('Mark failed'))

    const wrapper = mountPage()
    await flushPromises()

    const list = wrapper.findComponent(ReminderListStub)
    await list.vm.$emit('markReminded', 'c1')
    await flushPromises()

    expect(mockToast.error).toHaveBeenCalledWith('reminders.toast.markError')
  })

  it('loads more candidates appended on loadMore event', async () => {
    const firstPage = createReminderTodayResponse([createReminderCandidate({ clientId: 'c1' })], {
      meta: { total: 2, cursor: 'cursor-1' },
    })
    const secondPage = createReminderTodayResponse([createReminderCandidate({ clientId: 'c2' })], {
      meta: { total: 2, cursor: null },
    })
    mockGetTodayReminders.mockResolvedValueOnce(firstPage).mockResolvedValueOnce(secondPage)

    const wrapper = mountPage()
    await flushPromises()

    const pagination = wrapper.findComponent(ReminderListPaginationStub)
    await pagination.vm.$emit('loadMore')
    await flushPromises()

    const list = wrapper.findComponent(ReminderListStub)
    expect(list.props('candidates')).toHaveLength(2)
    expect(list.props('candidates').map((c: ReminderCandidate) => c.clientId)).toEqual(['c1', 'c2'])
  })
})
