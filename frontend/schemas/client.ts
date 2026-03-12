import { z } from 'zod'

export const clientSchema = z.object({
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  phone: z.string().min(1).max(20),
  email: z.string().email().max(180).optional().or(z.literal('')),
  notes: z.string().max(2000).optional().or(z.literal('')),
})

export type ClientFormValues = z.infer<typeof clientSchema>