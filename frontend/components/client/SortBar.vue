<script setup lang="ts">
const sort = defineModel<string>('sort', { default: 'created_at' })
const direction = defineModel<string>('direction', { default: 'desc' })

const { t } = useI18n()

const sortOptions = [
  { value: 'created_at', label: computed(() => t('clients.sort.createdAt')) },
  { value: 'last_visit_at', label: computed(() => t('clients.sort.lastVisitAt')) },
  { value: 'last_name', label: computed(() => t('clients.sort.lastName')) },
]

function toggleDirection() {
  direction.value = direction.value === 'desc' ? 'asc' : 'desc'
}
</script>

<template>
  <div class="flex items-center gap-2">
    <select
      :value="sort"
      class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
      @change="sort = ($event.target as HTMLSelectElement).value"
    >
      <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
        {{ opt.label.value }}
      </option>
    </select>
    <button
      type="button"
      class="rounded-lg border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :aria-label="direction === 'desc' ? t('clients.sort.descending') : t('clients.sort.ascending')"
      @click="toggleDirection"
    >
      <svg class="h-4 w-4 transition-transform" :class="direction === 'asc' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
  </div>
</template>