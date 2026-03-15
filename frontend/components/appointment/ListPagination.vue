<script setup lang="ts">
defineProps<{
  hasMore: boolean
  isLoading?: boolean
}>()

const emit = defineEmits<{
  loadMore: []
}>()

const { t } = useI18n()
</script>

<template>
  <div v-if="hasMore" class="mt-6 flex justify-center">
    <UiButton variant="secondary" :loading="isLoading" @click="emit('loadMore')">
      {{ t('appointments.list.loadMore') }}
    </UiButton>
  </div>
</template>
