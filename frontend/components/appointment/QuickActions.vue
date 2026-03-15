<script setup lang="ts">
import type { AppointmentStatus } from '~/types/appointment'

defineProps<{
  status: AppointmentStatus
  loading?: boolean
}>()

const emit = defineEmits<{
  view: []
  complete: []
  noShow: []
  cancel: []
}>()

const { t } = useI18n()
</script>

<template>
  <div class="flex items-center gap-2 flex-wrap">
    <UiButton variant="ghost" size="sm" :loading="loading" @click="emit('view')">
      {{ t('appointments.actions.view') }}
    </UiButton>
    <template v-if="status === 'scheduled'">
      <UiButton variant="secondary" size="sm" :loading="loading" @click="emit('complete')">
        {{ t('appointments.actions.complete') }}
      </UiButton>
      <UiButton variant="ghost" size="sm" :loading="loading" @click="emit('noShow')">
        {{ t('appointments.actions.noShow') }}
      </UiButton>
      <UiButton variant="danger" size="sm" :loading="loading" @click="emit('cancel')">
        {{ t('appointments.actions.cancel') }}
      </UiButton>
    </template>
  </div>
</template>
