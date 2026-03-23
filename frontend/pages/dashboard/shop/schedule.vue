<script setup lang="ts">
import type { ScheduleEntry } from '~/types/shop'

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

const formRef = ref<{ setGeneralError: (msg: string) => void } | null>(null)
const loading = ref(false)

onMounted(async () => {
  if (!shopStore.shop) {
    await shopStore.fetchShop()
  }
  if (!shopStore.hasShop) {
    await navigateTo(localePath('/dashboard/shop/create'))
  }
})

async function onSubmit(data: { schedule: ScheduleEntry[] }) {
  loading.value = true
  try {
    const response = await shopApi.updateSchedule(data)
    shopStore.setSchedule(response.schedule)
    toast.success('shop.schedule.updated')
    await navigateTo(localePath('/dashboard/shop'))
  } catch (err) {
    const { error } = parseApiError(err)
    formRef.value?.setGeneralError(error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="shopStore.shop">
    <DashboardPageHeader :title="t('shop.schedule.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/shop')">
          <UiButton variant="secondary">{{ t('common.back') }}</UiButton>
        </NuxtLink>
      </template>
    </DashboardPageHeader>
    <UiBreadcrumb
      :items="[
        { label: t('nav.shop'), to: localePath('/dashboard/shop') },
        { label: t('shop.schedule.title') },
      ]"
    />

    <div class="mx-auto max-w-2xl">
      <ShopScheduleForm
        ref="formRef"
        :schedule="shopStore.schedule"
        :loading="loading"
        @submit="onSubmit"
      />
    </div>
  </div>

  <div v-else-if="shopStore.isLoading" class="flex justify-center py-12">
    <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
  </div>
</template>