<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    type?: 'text' | 'email' | 'password'
    label: string
    placeholder?: string
    error?: string
    required?: boolean
    autocomplete?: string
    disabled?: boolean
  }>(),
  {
    type: 'text',
    placeholder: '',
    error: undefined,
    required: false,
    autocomplete: '',
    disabled: false,
  },
)

const model = defineModel<string>({ required: true })

const inputId = useId()
const errorId = `${inputId}-error`
</script>

<template>
  <div>
    <label :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ props.label }}
      <span v-if="props.required" class="text-error" aria-hidden="true">*</span>
    </label>
    <input
      :id="inputId"
      v-model="model"
      :type="props.type"
      :placeholder="props.placeholder"
      :required="props.required"
      :autocomplete="props.autocomplete"
      :disabled="props.disabled"
      :aria-invalid="!!props.error"
      :aria-describedby="props.error ? errorId : undefined"
      class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-2 hover:border-gray-400"
      :class="[
        props.error
          ? 'border-error focus:border-error focus:ring-error/15'
          : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15',
        props.disabled ? 'cursor-not-allowed bg-gray-100 border-gray-200' : 'bg-white',
      ]"
    />
    <p v-if="props.error" :id="errorId" class="mt-1 text-sm text-error" role="alert">
      {{ props.error }}
    </p>
  </div>
</template>