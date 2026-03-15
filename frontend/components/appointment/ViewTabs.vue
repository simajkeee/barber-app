<script setup lang="ts">
defineProps<{
  tab: 'daily' | 'list'
}>()

const emit = defineEmits<{
  'update:tab': ['daily' | 'list']
}>()

const { t } = useI18n()
</script>

<template>
  <div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
      <button
        type="button"
        class="border-b-2 py-3 text-sm font-medium transition-colors focus:outline-none"
        :class="tab === 'daily'
          ? 'border-primary-700 text-primary-700'
          : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
        @click="emit('update:tab', 'daily')"
      >
        {{ t('appointments.tabs.daily') }}
      </button>
      <button
        type="button"
        class="border-b-2 py-3 text-sm font-medium transition-colors focus:outline-none"
        :class="tab === 'list'
          ? 'border-primary-700 text-primary-700'
          : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
        @click="emit('update:tab', 'list')"
      >
        {{ t('appointments.tabs.list') }}
      </button>
    </nav>
  </div>
</template>
