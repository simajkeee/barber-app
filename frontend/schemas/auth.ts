import { z } from 'zod'

export const loginSchema = z.object({
  email: z.string().min(1).email(),
  password: z.string().min(1),
})

export const registerSchema = z.object({
  firstName: z.string().min(1),
  lastName: z.string().min(1),
  email: z.string().min(1).email(),
  password: z.string().min(8),
})

export const forgotPasswordSchema = z.object({
  email: z.string().min(1).email(),
})

export const resetPasswordSchema = z.object({
  password: z.string().min(8),
})

export type LoginFormValues = z.infer<typeof loginSchema>
export type RegisterFormValues = z.infer<typeof registerSchema>
export type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>
export type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>