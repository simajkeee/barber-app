<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const authStore = useAuthStore()
const shopStore = useShopStore()

onMounted(async () => {
  if (!shopStore.shop && !shopStore.isLoading) {
    await shopStore.fetchShop()
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900">{{ t('dashboard.title') }}</h1>
    <p class="mt-2 text-gray-500">{{ authStore.fullName }}</p>

    <div v-if="shopStore.isLoading" class="mt-8 flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <div v-else-if="!shopStore.hasShop" class="mt-8">
      <UiEmptyState
        :title="t('dashboard.noShop')"
        :description="t('dashboard.noShopDescription')"
      >
        <template #action>
          <NuxtLink :to="localePath('/dashboard/shop/create')">
            <UiButton>{{ t('shop.create.title') }}</UiButton>
          </NuxtLink>
        </template>
      </UiEmptyState>
    </div>

    <div v-else class="mt-8">
      <p class="text-gray-600">{{ shopStore.shopName }}</p>
    </div>
  </div>
</template>