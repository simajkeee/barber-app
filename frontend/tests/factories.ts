import type { User, AuthResponse } from '~/types/auth'

export function createUser(overrides: Partial<User> = {}): User {
  return {
    id: '1',
    email: 'test@example.com',
    firstName: 'John',
    lastName: 'Doe',
    avatarUrl: null,
    locale: 'vi',
    ...overrides,
  }
}

export function createAuthResponse(overrides: Partial<AuthResponse> = {}): AuthResponse {
  return {
    user: createUser(),
    token: 'access-token-123',
    refreshToken: 'refresh-token-456',
    ...overrides,
  }
}