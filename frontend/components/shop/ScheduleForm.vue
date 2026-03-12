<script setup lang="ts">
import type { ScheduleEntry, ScheduleEntryForm, DayOfWeek } from '~/types/shop'

const props = withDefaults(
  defineProps<{
    schedule: ScheduleEntry[]
    loading?: boolean
  }>(),
  { loading: false },
)

const emit = defineEmits<{
  submit: [data: { schedule: ScheduleEntry[] }]
}>()

const { t } = useI18n()

const days: DayOfWeek[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

const entries = ref<ScheduleEntryForm[]>(
  days.map((day) => {
    const existing = props.schedule.find((s) => s.dayOfWeek === day)
    return {
      dayOfWeek: day,
      openTime: existing?.openTime ?? '09:00',
      closeTime: existing?.closeTime ?? '18:00',
      isOpen: existing?.isOpen ?? true,
    }
  }),
)

const rowErrors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

function validate(): boolean {
  rowErrors.value = {}
  let valid = true

  for (const entry of entries.value) {
    if (entry.isOpen && entry.openTime >= entry.closeTime) {
      rowErrors.value[entry.dayOfWeek] = t('shop.error.scheduleOpenBeforeClose')
      valid = false
    }
  }

  return valid
}

function onSubmit() {
  generalError.value = null
  if (!validate()) return

  const schedule: ScheduleEntry[] = entries.value.map((e) => ({
    dayOfWeek: e.dayOfWeek,
    openTime: e.isOpen ? e.openTime : null,
    closeTime: e.isOpen ? e.closeTime : null,
    isOpen: e.isOpen,
  }))

  emit('submit', { schedule })
}

function setGeneralError(msg: string) {
  generalError.value = msg
}

defineExpose({ setGeneralError })
</script>

<template>
  <form @submit.prevent="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-3">
      <ShopScheduleDayRow
        v-for="(entry, i) in entries"
        :key="entry.dayOfWeek"
        v-model="entries[i]"
        :day-of-week="entry.dayOfWeek"
        :error="rowErrors[entry.dayOfWeek]"
      />
    </div>

    <div class="mt-6">
      <UiButton type="submit" :loading="loading">
        {{ t('shop.schedule.save') }}
      </UiButton>
    </div>
  </form>
</template>