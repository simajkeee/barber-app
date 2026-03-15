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

const open = ref(false)
const listRef = ref<HTMLElement | null>(null)
const triggerRef = ref<HTMLButtonElement | null>(null)

function select(time: string) {
  model.value = time
  open.value = false
}

function toggle() {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) {
    nextTick(() => {
      const active = listRef.value?.querySelector('[data-active]') as HTMLElement | null
      active?.scrollIntoView({ block: 'nearest' })
    })
  }
}

function onDocumentClick(e: MouseEvent) {
  if (
    !listRef.value?.contains(e.target as Node) &&
    !triggerRef.value?.contains(e.target as Node)
  ) {
    open.value = false
  }
}

watch(open, (val) => {
  if (val) document.addEventListener('click', onDocumentClick, { capture: true })
  else document.removeEventListener('click', onDocumentClick, { capture: true })
})

onUnmounted(() => document.removeEventListener('click', onDocumentClick, { capture: true }))
</script>

<template>
  <div>
    <label :for="inputId" class="mb-1 block text-sm font-medium text-gray-700">
      {{ props.label }}
    </label>
    <div class="relative">
      <button
        :id="inputId"
        ref="triggerRef"
        type="button"
        :disabled="props.disabled"
        :aria-haspopup="'listbox'"
        :aria-expanded="open"
        :aria-invalid="!!props.error"
        :aria-describedby="props.error ? errorId : undefined"
        class="flex w-full items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-colors focus:outline-none focus:ring-2 hover:border-gray-400"
        :class="[
          props.error
            ? 'border-error focus:border-error focus:ring-error/15'
            : 'border-gray-300 focus:border-primary-700 focus:ring-primary-700/15',
          props.disabled ? 'cursor-not-allowed bg-gray-100 border-gray-200 text-gray-400' : 'cursor-pointer bg-white text-gray-900',
        ]"
        @click="toggle"
      >
        <span>{{ model ?? '--:--' }}</span>
        <svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </button>

      <ul
        v-if="open"
        ref="listRef"
        role="listbox"
        class="absolute z-50 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
      >
        <li
          v-for="time in timeOptions"
          :key="time"
          :data-active="time === model ? '' : undefined"
          role="option"
          :aria-selected="time === model"
          class="cursor-pointer px-3 py-1.5 text-sm hover:bg-gray-50"
          :class="time === model ? 'bg-primary-50 font-medium text-primary-700' : 'text-gray-900'"
          @click="select(time)"
        >
          {{ time }}
        </li>
      </ul>
    </div>
    <p v-if="props.error" :id="errorId" class="mt-1 text-sm text-error" role="alert">
      {{ props.error }}
    </p>
  </div>
</template>
