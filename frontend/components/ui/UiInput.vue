<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    type?: 'text' | 'email' | 'password'
    label?: string
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

const isPasswordVisible = ref(false)
const isPasswordType = props.type === 'password'
const inputType = computed(() =>
  isPasswordType && isPasswordVisible.value ? 'text' : props.type,
)
</script>

<template>
  <div>
    <label v-if="props.label" :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ props.label }}
      <span v-if="props.required" class="text-error" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <input
        :id="inputId"
        v-model="model"
        :type="inputType"
        :placeholder="props.placeholder"
        :required="props.required"
        :autocomplete="props.autocomplete"
        :disabled="props.disabled"
        :aria-label="props.label ? undefined : props.placeholder"
        :aria-invalid="!!props.error"
        :aria-describedby="props.error ? errorId : undefined"
        class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-2 hover:border-gray-400"
        :class="[
          props.error
            ? 'border-error focus:border-error focus:ring-error/15'
            : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15',
          props.disabled ? 'cursor-not-allowed bg-gray-100 border-gray-200' : 'bg-white',
          isPasswordType ? 'pr-10' : '',
        ]"
      />
      <button
        v-if="isPasswordType"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
        :aria-label="isPasswordVisible ? 'Hide password' : 'Show password'"
        @click="isPasswordVisible = !isPasswordVisible"
      >
        <svg v-if="!isPasswordVisible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
      </button>
    </div>
    <p v-if="props.error" :id="errorId" class="mt-1 text-sm text-error" role="alert">
      {{ props.error }}
    </p>
  </div>
</template>