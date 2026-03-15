<script setup lang="ts">
const props = defineProps<{
  currentStep: number
  totalSteps: number
  labels: string[]
}>()
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <div
      v-for="(label, index) in labels"
      :key="index"
      class="flex items-center"
      :class="{ 'flex-1': index < labels.length - 1 }"
    >
      <div class="flex items-center gap-2">
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          :class="
            index + 1 <= currentStep
              ? 'bg-primary-600 text-white'
              : 'bg-gray-200 text-gray-500'
          "
        >
          {{ index + 1 }}
        </div>
        <span
          class="hidden sm:inline text-sm"
          :class="index + 1 <= currentStep ? 'text-primary-700 font-medium' : 'text-gray-400'"
        >
          {{ label }}
        </span>
      </div>
      <div
        v-if="index < labels.length - 1"
        class="flex-1 h-px mx-3"
        :class="index + 1 < currentStep ? 'bg-primary-400' : 'bg-gray-200'"
      />
    </div>
  </div>
</template>
