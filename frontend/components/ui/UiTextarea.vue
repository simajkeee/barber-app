<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    placeholder?: string
    error?: string
    required?: boolean
    disabled?: boolean
    rows?: number
  }>(),
  {
    placeholder: '',
    error: undefined,
    required: false,
    disabled: false,
    rows: 3,
  },
)

const model = defineModel<string>({ required: true })

const inputId = useId()
const errorId = `${inputId}-error`
</script>

<template>
  <div>
    <label :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ label }}
      <span v-if="required" class="text-error" aria-hidden="true">*</span>
    </label>
    <textarea
      :id="inputId"
      v-model="model"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      :aria-invalid="!!error"
      :aria-describedby="error ? errorId : undefined"
      class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-2 hover:border-gray-400"
      :class="[
        error
          ? 'border-error focus:border-error focus:ring-error/15'
          : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15',
        disabled ? 'cursor-not-allowed bg-gray-100 border-gray-200' : 'bg-white',
      ]"
    />
    <p v-if="error" :id="errorId" class="mt-1 text-sm text-error" role="alert">
      {{ error }}
    </p>
  </div>
</template>