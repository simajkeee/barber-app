<script setup lang="ts">
defineProps<{
  hasMore: boolean
  isLoading: boolean
}>()

const emit = defineEmits<{
  loadMore: []
}>()

const { t } = useI18n()
</script>

<template>
  <div v-if="hasMore" class="mt-6 text-center">
    <UiButton variant="secondary" :disabled="isLoading" @click="emit('loadMore')">
      {{ isLoading ? t('common.loading') : t('reminders.loadMore') }}
    </UiButton>
  </div>
</template>
