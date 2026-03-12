<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    open: boolean
    title: string
    description?: string
    confirmLabel?: string
    cancelLabel?: string
    variant?: 'danger' | 'primary'
    loading?: boolean
  }>(),
  {
    description: undefined,
    confirmLabel: undefined,
    cancelLabel: undefined,
    variant: 'danger',
    loading: false,
  },
)

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const { t } = useI18n()
</script>

<template>
  <UiModal
    :open="props.open"
    :title="props.title"
    @close="emit('cancel')"
  >
    <p v-if="props.description" class="text-sm text-gray-600">
      {{ props.description }}
    </p>
    <template #footer>
      <div class="flex justify-end gap-3">
        <UiButton variant="secondary" :disabled="props.loading" @click="emit('cancel')">
          {{ props.cancelLabel ?? t('common.cancel') }}
        </UiButton>
        <UiButton
          :variant="props.variant === 'danger' ? 'danger' : 'primary'"
          :loading="props.loading"
          @click="emit('confirm')"
        >
          {{ props.confirmLabel ?? t('common.confirm') }}
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>