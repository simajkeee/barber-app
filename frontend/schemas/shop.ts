import { z } from 'zod'

export const createShopSchema = z.object({
  name: z.string().min(1).max(255),
  address: z.string().min(1).max(500),
  phone: z.string().min(1).max(20),
  description: z.string().max(2000).optional(),
})

export const updateShopSchema = z.object({
  name: z.string().min(1).max(255).optional(),
  address: z.string().min(1).max(500).optional(),
  phone: z.string().min(1).max(20).optional(),
  description: z.string().max(2000).optional(),
  slug: z.string().regex(/^[a-z0-9][a-z0-9-]{2,97}[a-z0-9]$/).optional(),
  coverImageUrl: z.string().max(500).optional(),
})

export const createServiceSchema = z.object({
  name: z.string().min(1).max(255),
  durationMinutes: z.coerce.number().int().min(5).max(480),
  price: z.coerce.number().int().min(1000),
  sortOrder: z.coerce.number().int().min(0).optional(),
})

export const updateServiceSchema = z.object({
  name: z.string().min(1).max(255).optional(),
  durationMinutes: z.coerce.number().int().min(5).max(480).optional(),
  price: z.coerce.number().int().min(1000).optional(),
  sortOrder: z.coerce.number().int().min(0).optional(),
})

export type CreateShopFormValues = z.infer<typeof createShopSchema>
export type UpdateShopFormValues = z.infer<typeof updateShopSchema>
export type CreateServiceFormValues = z.infer<typeof createServiceSchema>
export type UpdateServiceFormValues = z.infer<typeof updateServiceSchema>