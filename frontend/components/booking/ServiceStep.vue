<script setup lang="ts">
import type { PublicService } from '~/types/booking'

defineProps<{
  services: PublicService[]
  selectedId: string | null
}>()

const emit = defineEmits<{
  select: [service: PublicService]
}>()

const { formatPrice, formatDuration } = useFormatters()
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('booking.steps.service') }}</h2>

    <p v-if="services.length === 0" class="text-sm text-gray-500 text-center py-6">
      {{ $t('booking.steps.noServices') }}
    </p>

    <div v-else class="grid gap-3">
      <button
        v-for="service in services"
        :key="service.id"
        type="button"
        class="w-full text-left p-4 rounded-lg border-2 transition-colors"
        :class="
          selectedId === service.id
            ? 'border-primary-500 bg-primary-50'
            : 'border-gray-200 hover:border-primary-300 bg-white'
        "
        @click="emit('select', service)"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">{{ service.name }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ formatDuration(service.duration) }}</p>
          </div>
          <span class="text-primary-700 font-semibold">{{ formatPrice(service.price) }}</span>
        </div>
      </button>
    </div>
  </div>
</template>
