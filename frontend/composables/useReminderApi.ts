import type {
  MarkRemindedResponse,
  ReminderSettings,
  ReminderTodayResponse,
  UpdateReminderSettingsRequest,
} from '~/types/reminder'

export function useReminderApi() {
  const api = useApi()

  async function getTodayReminders(params?: {
    limit?: number
    cursor?: string
  }): Promise<ReminderTodayResponse> {
    const query: Record<string, string | number> = {}
    if (params?.limit) query.limit = params.limit
    if (params?.cursor) query.cursor = params.cursor

    return api<ReminderTodayResponse>('/reminders/today', { query })
  }

  async function getSettings(locale?: 'vi' | 'en'): Promise<ReminderSettings> {
    const query: Record<string, string> = {}
    if (locale) query.locale = locale
    return api<ReminderSettings>('/reminders/settings', { query })
  }

  async function updateSettings(data: UpdateReminderSettingsRequest): Promise<ReminderSettings> {
    return api<ReminderSettings>('/reminders/settings', { method: 'PUT', body: data })
  }

  async function markReminded(clientId: string): Promise<MarkRemindedResponse> {
    return api<MarkRemindedResponse>(`/reminders/${clientId}/mark-reminded`, { method: 'POST' })
  }

  return { getTodayReminders, getSettings, updateSettings, markReminded }
}
