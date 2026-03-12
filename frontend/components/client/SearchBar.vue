<script setup lang="ts">
const model = defineModel<string>({ required: true })
const { t } = useI18n()

const localValue = ref(model.value)
let debounceTimer: ReturnType<typeof setTimeout> | undefined

watch(model, (val) => {
  if (val !== localValue.value) {
    localValue.value = val
  }
})

function onInput(value: string) {
  localValue.value = value
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    model.value = value
  }, 300)
}

function clear() {
  localValue.value = ''
  clearTimeout(debounceTimer)
  model.value = ''
}

onUnmounted(() => clearTimeout(debounceTimer))
</script>

<template>
  <div class="relative">
    <UiInput
      :model-value="localValue"
      :placeholder="t('clients.list.searchPlaceholder')"
      autocomplete="off"
      @update:model-value="onInput"
    />
    <button
      v-if="localValue"
      type="button"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
      :aria-label="t('clients.list.clearSearch')"
      @click="clear"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>