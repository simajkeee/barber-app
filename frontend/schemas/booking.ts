import { z } from 'zod'

export const bookingDetailsSchema = z.object({
  clientName: z.string().min(1).max(100),
  clientPhone: z.string().regex(/^(0|\+84)[0-9]{9}$/),
})
