<script setup lang="ts">
import type { ShopService } from '~/types/shop'

defineProps<{
  service: ShopService
}>()

const emit = defineEmits<{
  edit: [service: ShopService]
  delete: [serviceId: string]
  activate: [serviceId: string]
}>()

const { t } = useI18n()
const { formatPrice, formatDuration } = useFormatters()
</script>

<template>
  <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2">
        <h3 class="truncate text-sm font-medium text-gray-900">{{ service.name }}</h3>
        <UiStatusBadge
          :label="service.isActive ? t('shop.services.active') : t('shop.services.inactive')"
          :variant="service.isActive ? 'success' : 'neutral'"
        />
      </div>
      <p class="mt-1 text-sm text-gray-500">
        {{ formatDuration(service.durationMinutes) }} · {{ formatPrice(service.price) }}
      </p>
    </div>
    <div class="ml-4 flex shrink-0 gap-2">
      <UiButton variant="ghost" size="sm" @click="emit('edit', service)">
        {{ t('shop.profile.edit') }}
      </UiButton>
      <UiButton v-if="service.isActive" variant="ghost" size="sm" @click="emit('delete', service.id)">
        {{ t('shop.services.deactivate') }}
      </UiButton>
      <UiButton v-else variant="ghost" size="sm" @click="emit('activate', service.id)">
        {{ t('shop.services.activate') }}
      </UiButton>
    </div>
  </div>
</template>