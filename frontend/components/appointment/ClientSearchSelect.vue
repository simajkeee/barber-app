<script setup lang="ts">
import type { Client } from '~/types/client'

const props = defineProps<{
  modelValue: string | null
  clients: Client[]
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [id: string | null]
}>()

const { t } = useI18n()

const search = ref('')
const isOpen = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

const selectedClient = computed(() =>
  props.clients.find((c) => c.id === props.modelValue) ?? null,
)

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return props.clients.slice(0, 50)
  return props.clients
    .filter(
      (c) =>
        `${c.firstName} ${c.lastName}`.toLowerCase().includes(q) ||
        c.phone.includes(q),
    )
    .slice(0, 50)
})

const inputId = useId()
const errorId = `${inputId}-error`

function select(client: Client) {
  emit('update:modelValue', client.id)
  search.value = ''
  isOpen.value = false
}

function clear() {
  emit('update:modelValue', null)
  search.value = ''
  nextTick(() => inputRef.value?.focus())
}

function onFocus() {
  if (!selectedClient.value) isOpen.value = true
}

function onBlur() {
  setTimeout(() => {
    isOpen.value = false
  }, 150)
}
</script>

<template>
  <div>
    <label :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ t('appointments.form.client') }}
      <span class="text-error" aria-hidden="true">*</span>
    </label>

    <div class="relative">
      <!-- Selected client chip -->
      <div
        v-if="selectedClient"
        class="flex items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2.5"
        :class="error ? 'border-error' : 'border-gray-300'"
      >
        <div>
          <span class="text-sm font-medium text-gray-900">{{ selectedClient.firstName }} {{ selectedClient.lastName }}</span>
          <span class="ml-2 text-xs text-gray-500">{{ selectedClient.phone }}</span>
        </div>
        <button
          type="button"
          class="ml-2 text-gray-400 hover:text-gray-600 focus:outline-none"
          :aria-label="t('common.cancel')"
          @click="clear"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Search input -->
      <input
        v-else
        :id="inputId"
        ref="inputRef"
        v-model="search"
        type="text"
        :placeholder="t('appointments.form.clientSearch')"
        autocomplete="off"
        :aria-invalid="!!error"
        :aria-describedby="error ? errorId : undefined"
        class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 hover:border-gray-400"
        :class="error
          ? 'border-error focus:border-error focus:ring-error/15'
          : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15'"
        @focus="onFocus"
        @blur="onBlur"
      />

      <!-- Dropdown -->
      <ul
        v-if="isOpen && !selectedClient"
        class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"
        role="listbox"
      >
        <li
          v-if="filtered.length === 0"
          class="px-3 py-2.5 text-sm text-gray-500"
        >
          {{ t('clients.list.noResults') }}
        </li>
        <li
          v-for="client in filtered"
          :key="client.id"
          role="option"
          :aria-selected="client.id === modelValue"
          class="flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm hover:bg-primary-50 focus:bg-primary-50 focus:outline-none"
          @mousedown.prevent="select(client)"
        >
          <span class="font-medium text-gray-900">{{ client.firstName }} {{ client.lastName }}</span>
          <span class="text-xs text-gray-500">{{ client.phone }}</span>
        </li>
      </ul>
    </div>

    <p v-if="error" :id="errorId" class="mt-1 text-sm text-error" role="alert">{{ error }}</p>
  </div>
</template>
