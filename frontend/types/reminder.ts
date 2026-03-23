export interface ReminderCandidate {
  clientId: string
  clientName: string
  clientPhone: string
  daysSinceVisit: number
  lastVisitAt: string
  lastRemindedAt: string | null
  message: string
}

export interface ReminderSettings {
  daysSinceLastVisit: number
  messageTemplate: string
  locale: 'vi' | 'en'
}

export interface ReminderTodayResponse {
  data: ReminderCandidate[]
  meta: {
    total: number
    cursor: string | null
  }
  settings: ReminderSettings
}

export interface UpdateReminderSettingsRequest {
  daysSinceLastVisit?: number
  messageTemplate?: string
  locale?: 'vi' | 'en'
}

export interface MarkRemindedResponse {
  clientId: string
  lastRemindedAt: string
}
