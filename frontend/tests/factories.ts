import type { User, AuthResponse } from '~/types/auth'
import type { Shop, ShopService, ScheduleEntry } from '~/types/shop'
import type { Client, ClientListResponse, ClientPagination } from '~/types/client'
import type {
  Appointment,
  AppointmentClient,
  AppointmentService,
  AppointmentListResponse,
  DailyScheduleResponse,
  TimeSlot,
} from '~/types/appointment'
import type { SubscriptionResponse } from '~/types/subscription'

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

export function createClient(overrides: Partial<Client> = {}): Client {
  return {
    id: 'client-1',
    firstName: 'Nguyen',
    lastName: 'Van A',
    phone: '+84901234567',
    email: 'nguyen@example.com',
    notes: null,
    lastVisitAt: null,
    visitCount: 0,
    createdAt: '2026-03-01T10:00:00Z',
    updatedAt: '2026-03-01T10:00:00Z',
    ...overrides,
  }
}

export function createClientListResponse(
  clients: Client[] = [createClient()],
  pagination: Partial<ClientPagination> = {},
): ClientListResponse {
  return {
    data: clients,
    pagination: {
      nextCursor: null,
      hasMore: false,
      ...pagination,
    },
  }
}

export function createAppointmentClient(overrides: Partial<AppointmentClient> = {}): AppointmentClient {
  return {
    id: 'client-1',
    firstName: 'Nguyen',
    lastName: 'Van A',
    phone: '+84901234567',
    ...overrides,
  }
}

export function createAppointmentService(overrides: Partial<AppointmentService> = {}): AppointmentService {
  return {
    id: 'service-1',
    name: 'Haircut',
    durationMinutes: 30,
    price: 150000,
    ...overrides,
  }
}

export function createAppointment(overrides: Partial<Appointment> = {}): Appointment {
  return {
    id: 'appt-1',
    client: createAppointmentClient(),
    service: createAppointmentService(),
    startTime: '2026-03-15T02:00:00.000Z', // 09:00 Ho Chi Minh (+07:00)
    endTime: '2026-03-15T02:30:00.000Z',   // 09:30 Ho Chi Minh
    status: 'scheduled',
    notes: null,
    createdAt: '2026-03-01T00:00:00.000Z',
    updatedAt: '2026-03-01T00:00:00.000Z',
    ...overrides,
  }
}

export function createTimeSlot(overrides: Partial<TimeSlot> = {}): TimeSlot {
  return {
    startTime: '2026-03-15T02:00:00.000Z',
    endTime: '2026-03-15T02:30:00.000Z',
    ...overrides,
  }
}

export function createDailyScheduleResponse(overrides: Partial<DailyScheduleResponse> = {}): DailyScheduleResponse {
  return {
    date: '2026-03-15',
    workingHours: { openTime: '09:00', closeTime: '18:00' },
    appointments: [createAppointment()],
    ...overrides,
  }
}

export function createAppointmentListResponse(
  appointments: Appointment[] = [createAppointment()],
  cursor: string | null = null,
): AppointmentListResponse {
  return { data: appointments, cursor }
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

export function createSubscriptionResponse(
  overrides: Partial<SubscriptionResponse> = {},
): SubscriptionResponse {
  return {
    id: 'sub-1',
    plan: 'free',
    status: 'active',
    startDate: '2026-03-01T00:00:00+07:00',
    endDate: null,
    usage: {
      appointmentsThisMonth: 18,
      appointmentLimit: 50,
      limitReached: false,
    },
    ...overrides,
  }
}