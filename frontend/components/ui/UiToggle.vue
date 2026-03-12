<script setup lang="ts">
defineProps<{
  label: string
  disabled?: boolean
}>()

const model = defineModel<boolean>({ required: true })
</script>

<template>
  <label class="inline-flex cursor-pointer items-center gap-3" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
    <button
      type="button"
      role="switch"
      :aria-checked="model"
      :aria-label="label"
      :disabled="disabled"
      class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:ring-offset-2"
      :class="model ? 'bg-primary-700' : 'bg-gray-300'"
      @click="model = !model"
    >
      <span
        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform"
        :class="model ? 'translate-x-5' : 'translate-x-0'"
      />
    </button>
    <span class="text-sm text-gray-700">{{ label }}</span>
  </label>
</template>