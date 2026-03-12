<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    label: string
    error?: string
    disabled?: boolean
  }>(),
  {
    error: undefined,
    disabled: false,
  },
)

const model = defineModel<string | null>({ required: true })

const inputId = useId()
const errorId = `${inputId}-error`

const timeOptions = computed(() => {
  const options: string[] = []
  for (let h = 6; h <= 23; h++) {
    for (let m = 0; m < 60; m += 15) {
      if (h === 23 && m > 45) break
      options.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)
    }
  }
  return options
})
</script>

<template>
  <div>
    <label :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ props.label }}
    </label>
    <div class="relative">
      <select
        :id="inputId"
        :value="model ?? ''"
        :disabled="props.disabled"
        :aria-invalid="!!props.error"
        :aria-describedby="props.error ? errorId : undefined"
        class="block w-full cursor-pointer appearance-none rounded-lg border px-3 pr-9 py-2.5 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-2 hover:border-gray-400"
        :class="[
          props.error
            ? 'border-error focus:border-error focus:ring-error/15'
            : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15',
          props.disabled ? 'cursor-not-allowed bg-gray-100 border-gray-200' : 'bg-white',
        ]"
        @change="model = ($event.target as HTMLSelectElement).value || null"
      >
        <option value="" disabled>--:--</option>
        <option v-for="time in timeOptions" :key="time" :value="time">
          {{ time }}
        </option>
      </select>
      <svg class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
      </svg>
    </div>
    <p v-if="props.error" :id="errorId" class="mt-1 text-sm text-error" role="alert">
      {{ props.error }}
    </p>
  </div>
</template>