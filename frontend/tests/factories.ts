import type { User, AuthResponse } from '~/types/auth'
import type { Shop, ShopService, ScheduleEntry } from '~/types/shop'

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

export function createShop(overrides: Partial<Shop> = {}): Shop {
  return {
    id: 'shop-1',
    name: 'Test Barber Shop',
    address: '123 Main Street',
    phone: '0901234567',
    description: 'A great barber shop',
    slug: 'test-barber-shop',
    coverImageUrl: null,
    schedule: createDefaultSchedule(),
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

export function createShopService(overrides: Partial<ShopService> = {}): ShopService {
  return {
    id: 'service-1',
    name: 'Haircut',
    durationMinutes: 30,
    price: 100000,
    isActive: true,
    sortOrder: 0,
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

export function createScheduleEntry(overrides: Partial<ScheduleEntry> = {}): ScheduleEntry {
  return {
    dayOfWeek: 'monday',
    openTime: '09:00',
    closeTime: '18:00',
    isOpen: true,
    ...overrides,
  }
}

export function createDefaultSchedule(): ScheduleEntry[] {
  const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const
  return days.map((day) => ({
    dayOfWeek: day,
    openTime: day === 'sunday' ? null : '09:00',
    closeTime: day === 'sunday' ? null : '18:00',
    isOpen: day !== 'sunday',
  }))
}