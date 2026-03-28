<script setup lang="ts">
import type { Appointment, AppointmentListFilter, DailyScheduleResponse } from '~/types/appointment'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const appointmentApi = useAppointmentApi()
const { parseApiError } = useApiError()
const toast = useToast()

// Tab state
const activeTab = ref<'daily' | 'list'>('daily')

// ───── Daily schedule ─────
function todayDate() {
  return new Date().toISOString().slice(0, 10)
}

const selectedDate = ref(todayDate())
const isDailyLoading = ref(false)

const { data: initialSchedule } = await useAsyncData(
  `daily-schedule-${selectedDate.value}`,
  () => appointmentApi.getDailySchedule(selectedDate.value),
)
const dailySchedule = ref<DailyScheduleResponse | null>(initialSchedule.value ?? null)

async function loadDailySchedule() {
  isDailyLoading.value = true
  try {
    dailySchedule.value = await appointmentApi.getDailySchedule(selectedDate.value)
  } catch {
    toast.error('appointments.toast.loadError')
  } finally {
    isDailyLoading.value = false
  }
}

watch(selectedDate, () => loadDailySchedule())

// ───── List ─────
const listFilters = ref<AppointmentListFilter>({})
const appointments = ref<Appointment[]>([])
const nextCursor = ref<string | null>(null)
const isListLoading = ref(false)
const isLoadingMore = ref(false)

async function loadAppointments(append = false) {
  if (append) {
    isLoadingMore.value = true
  } else {
    isListLoading.value = true
    nextCursor.value = null
  }

  try {
    const response = await appointmentApi.listAppointments({
      ...listFilters.value,
      cursor: append ? (nextCursor.value ?? undefined) : undefined,
    })
    appointments.value = append ? [...appointments.value, ...response.data] : response.data
    nextCursor.value = response.cursor
  } catch {
    toast.error('appointments.toast.loadError')
  } finally {
    isListLoading.value = false
    isLoadingMore.value = false
  }
}

watch(listFilters, () => loadAppointments(), { deep: true })

// ───── Status actions ─────
const actionLoading = ref(false)
const pendingAction = ref<{ id: string; type: 'complete' | 'noShow' | 'cancel' } | null>(null)
const confirmOpen = ref(false)

function promptAction(id: string, type: 'complete' | 'noShow' | 'cancel') {
  pendingAction.value = { id, type }
  confirmOpen.value = true
}

async function confirmAction() {
  if (!pendingAction.value) return
  actionLoading.value = true
  const { id, type } = pendingAction.value
  try {
    if (type === 'cancel') {
      await appointmentApi.cancelAppointment(id)
      toast.success('appointments.toast.cancelled')
    } else if (type === 'complete') {
      await appointmentApi.changeStatus(id, 'completed')
      toast.success('appointments.toast.completed')
    } else {
      await appointmentApi.changeStatus(id, 'no_show')
      toast.success('appointments.toast.noShow')
    }
    confirmOpen.value = false
    pendingAction.value = null
    await Promise.all([loadDailySchedule(), activeTab.value === 'list' ? loadAppointments() : Promise.resolve()])
  } catch (err) {
    const { error } = parseApiError(err)
    if (error === 'APPOINTMENT_NOT_MODIFIABLE') {
      toast.error('appointments.error.notModifiable')
    } else {
      toast.error('appointments.toast.statusError')
    }
  } finally {
    actionLoading.value = false
  }
}

function onView(id: string) {
  navigateTo(localePath(`/dashboard/appointments/${id}`))
}

const confirmTitle = computed(() => {
  if (!pendingAction.value) return ''
  const map = { complete: 'completeTitle', noShow: 'noShowTitle', cancel: 'cancelTitle' }
  return t(`appointments.confirm.${map[pendingAction.value.type]}`)
})

const confirmDescription = computed(() => {
  if (!pendingAction.value) return ''
  const map = { complete: 'completeDescription', noShow: 'noShowDescription', cancel: 'cancelDescription' }
  return t(`appointments.confirm.${map[pendingAction.value.type]}`)
})

watch(activeTab, (tab) => {
  if (tab === 'list' && appointments.value.length === 0) {
    loadAppointments()
  }
})
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('appointments.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/appointments/create')">
          <UiButton>{{ t('appointments.newButton') }}</UiButton>
        </NuxtLink>
      </template>
    </DashboardPageHeader>

    <AppointmentViewTabs v-model:tab="activeTab" />

    <!-- Daily Schedule Tab -->
    <div v-if="activeTab === 'daily'">
      <AppointmentDayNav v-model:date="selectedDate" />
      <AppointmentWorkingHours v-if="!isDailyLoading && dailySchedule" :working-hours="dailySchedule.workingHours" />
      <AppointmentDailyList
        :appointments="dailySchedule?.appointments ?? []"
        :is-loading="isDailyLoading"
        :working-hours="dailySchedule?.workingHours ?? null"
        @view="onView"
        @complete="promptAction($event, 'complete')"
        @no-show="promptAction($event, 'noShow')"
        @cancel="promptAction($event, 'cancel')"
      />
    </div>

    <!-- All Appointments Tab -->
    <div v-else>
      <AppointmentListFilters v-model:filters="listFilters" />

      <div v-if="isListLoading" class="flex justify-center py-12">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
      </div>

      <template v-else>
        <UiEmptyState
          v-if="appointments.length === 0"
          :title="t('appointments.list.empty')"
          :description="t('appointments.list.emptyDescription')"
        />
        <div v-else class="space-y-3">
          <AppointmentCard
            v-for="appointment in appointments"
            :key="appointment.id"
            :appointment="appointment"
            @view="onView"
            @complete="promptAction($event, 'complete')"
            @no-show="promptAction($event, 'noShow')"
            @cancel="promptAction($event, 'cancel')"
          />
        </div>
      </template>

      <AppointmentListPagination
        :has-more="!!nextCursor"
        :is-loading="isLoadingMore"
        @load-more="loadAppointments(true)"
      />
    </div>

    <UiConfirmDialog
      :open="confirmOpen"
      :title="confirmTitle"
      :description="confirmDescription"
      :loading="actionLoading"
      :variant="pendingAction?.type === 'cancel' ? 'danger' : 'primary'"
      @confirm="confirmAction"
      @cancel="confirmOpen = false"
    />
  </div>
</template>
