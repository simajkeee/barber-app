export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  avatarUrl: string | null
  locale: 'vi' | 'en'
}

export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterRequest {
  email: string
  password: string
  firstName: string
  lastName: string
  phoneNumber: string
  locale?: 'vi' | 'en'
}

export interface FacebookAuthRequest {
  accessToken: string
}

export interface UpdateProfileRequest {
  firstName?: string
  lastName?: string
  locale?: 'vi' | 'en'
}

export interface AuthResponse {
  user: User
  token: string
  refreshToken: string
  isNewUser?: boolean
}

export interface TokenRefreshResponse {
  token: string
  refreshToken: string
}

export interface ApiValidationError {
  code: string
  message: string
  details?: Array<{ field: string; message: string }>
}

export type AuthResult =
  | { success: true }
  | { success: false; error: string; fieldErrors?: Record<string, string> }