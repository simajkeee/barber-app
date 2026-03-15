import { z } from 'zod'

export const reminderSettingsSchema = z.object({
  daysSinceLastVisit: z.coerce.number().int().min(1).max(365),
  messageTemplate: z.string().min(1).max(1000),
})

export type ReminderSettingsFormValues = z.infer<typeof reminderSettingsSchema>
