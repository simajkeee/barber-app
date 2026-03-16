<script setup lang="ts">
import type { PublicShopInfo } from '~/types/booking'

defineProps<{
  shop: PublicShopInfo
}>()
</script>

<template>
  <div class="bg-white rounded-lg shadow-card p-6 mb-6">
    <h1 class="text-2xl font-bold text-primary-900">{{ shop.name }}</h1>
    <div class="mt-2 space-y-1 text-sm text-gray-600">
      <p v-if="shop.address" class="flex items-center gap-2">
        <span aria-hidden="true">📍</span>
        {{ shop.address }}
      </p>
      <p v-if="shop.phone" class="flex items-center gap-2">
        <span aria-hidden="true">📞</span>
        <a :href="`tel:${shop.phone}`" class="text-primary-600 hover:underline">{{ shop.phone }}</a>
      </p>
    </div>
  </div>
</template>
