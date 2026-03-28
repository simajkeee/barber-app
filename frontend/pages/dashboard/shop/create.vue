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

const formRef = ref<{ setError: (field: string, message: string) => void } | null>(null)
const loading = ref(false)

async function onSubmit(data: CreateShopRequest | UpdateShopRequest) {
  loading.value = true
  try {
    const response = await shopApi.createShop(data as CreateShopRequest)
    shopStore.setShop(response.shop)
    toast.success('shop.create.success')
    await navigateTo(localePath('/dashboard/shop'))
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        formRef.value?.setError(field, message)
      }
    } else if (error === 'SHOP_ALREADY_EXISTS') {
      formRef.value?.setError('_general', t('shop.error.shopAlreadyExists'))
    } else {
      formRef.value?.setError('_general', error)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <DashboardPageHeader
      :title="t('shop.create.title')"
      :subtitle="t('shop.create.subtitle')"
    />
    <UiBreadcrumb
      :items="[
        { label: t('dashboard.title'), to: localePath('/dashboard') },
        { label: t('shop.create.title') },
      ]"
    />
    <ShopProfileForm ref="formRef" :loading="loading" @submit="onSubmit" @cancel="navigateTo(localePath('/dashboard'))" />
  </div>
</template>