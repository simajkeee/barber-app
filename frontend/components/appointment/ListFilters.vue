<script setup lang="ts">
import type { AppointmentListFilter } from '~/types/appointment'

const props = defineProps<{
  filters: AppointmentListFilter
}>()

const emit = defineEmits<{
  'update:filters': [filters: AppointmentListFilter]
}>()

const { t } = useI18n()

const dateFrom = computed({
  get: () => props.filters.dateFrom ?? '',
  set: (v) => emit('update:filters', { ...props.filters, dateFrom: v || undefined }),
})

const dateTo = computed({
  get: () => props.filters.dateTo ?? '',
  set: (v) => emit('update:filters', { ...props.filters, dateTo: v || undefined }),
})

const status = computed({
  get: () => props.filters.status ?? [],
  set: (v) => emit('update:filters', { ...props.filters, status: v.length ? v : undefined }),
})

const hasFilters = computed(() =>
  !!(props.filters.dateFrom || props.filters.dateTo || props.filters.status?.length),
)

function clearFilters() {
  emit('update:filters', {})
}
</script>

<template>
  <div class="mb-4 space-y-3">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <div class="flex flex-col gap-1">
        <label for="filter-date-from" class="text-xs font-medium text-gray-600">{{ t('appointments.filters.dateFrom') }}</label>
        <input
          id="filter-date-from"
          v-model="dateFrom"
          type="date"
          class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:border-primary-700 focus:ring-primary-700/15"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label for="filter-date-to" class="text-xs font-medium text-gray-600">{{ t('appointments.filters.dateTo') }}</label>
        <input
          id="filter-date-to"
          v-model="dateTo"
          type="date"
          class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:border-primary-700 focus:ring-primary-700/15"
        />
      </div>
      <UiButton v-if="hasFilters" variant="ghost" size="sm" @click="clearFilters">
        {{ t('appointments.filters.clearFilters') }}
      </UiButton>
    </div>
    <div class="flex flex-col gap-1">
      <label class="text-xs font-medium text-gray-600">{{ t('appointments.filters.status') }}</label>
      <AppointmentStatusFilter v-model="status" />
    </div>
  </div>
</template>
