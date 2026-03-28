<script setup lang="ts">
import type { CreateShopRequest, UpdateShopRequest } from '~/types/shop'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

const { t } = useI18n()
const localePath = useLocalePath()
const shopStore = useShopStore()
const shopApi = useShopApi()
const { parseApiError } = useApiError()
const toast = useToast()

const isEditing = ref(false)
const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const loading = ref(false)

await useAsyncData('shop-profile-init', async () => {
  if (!shopStore.shop) {
    await shopStore.fetchShop()
  }
  if (!shopStore.hasShop) {
    await navigateTo(localePath('/dashboard/shop/create'))
  }
  return null
})

async function onSubmit(data: CreateShopRequest | UpdateShopRequest) {
  loading.value = true
  try {
    const response = await shopApi.updateShop(data as UpdateShopRequest)
    shopStore.setShop(response.shop)
    isEditing.value = false
    toast.success('shop.profile.updated')
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        formRef.value?.setError(field, message)
      }
    } else if (error === 'SLUG_ALREADY_EXISTS') {
      formRef.value?.setError('slug', t('shop.error.slugAlreadyExists'))
    } else {
      formRef.value?.setError('_general', error)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="shopStore.shop">
    <DashboardPageHeader :title="t('shop.profile.title')">
      <template #actions>
        <UiButton v-if="!isEditing" variant="secondary" @click="isEditing = true">
          {{ t('shop.profile.edit') }}
        </UiButton>
      </template>
    </DashboardPageHeader>

    <div v-if="!isEditing" class="space-y-6">
      <ShopProfileCard :shop="shopStore.shop" />
      <ShopSchedulePreview :schedule="shopStore.schedule" />

      <div class="flex gap-3">
        <NuxtLink :to="localePath('/dashboard/shop/schedule')">
          <UiButton variant="secondary">{{ t('nav.workingHours') }} →</UiButton>
        </NuxtLink>
        <NuxtLink :to="localePath('/dashboard/shop/services')">
          <UiButton variant="secondary">{{ t('shop.services.title') }} →</UiButton>
        </NuxtLink>
      </div>
    </div>

    <div v-else class="mx-auto max-w-lg">
      <ShopProfileForm
        ref="formRef"
        :shop="shopStore.shop"
        :loading="loading"
        @submit="onSubmit"
        @cancel="isEditing = false"
      />
    </div>
  </div>
</template>
