import { z } from 'zod'

export const appointmentSchema = z.object({
  clientId: z.string().min(1),
  serviceId: z.string().min(1),
  startTime: z.string().min(1),
  notes: z.string().max(1000).optional().or(z.literal('')),
})

export type AppointmentFormValues = z.infer<typeof appointmentSchema>
