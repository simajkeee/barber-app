<script setup lang="ts">
import type { ShopService } from '~/types/shop'

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

const services = ref<ShopService[]>([])
const includeInactive = useState('services-include-inactive', () => false)
const isLoadingServices = ref(false)

const modalOpen = ref(false)
const editingService = ref<ShopService | undefined>(undefined)
const modalLoading = ref(false)
const modalRef = ref<{ setError: (field: string, message: string) => void } | null>(null)

const confirmOpen = ref(false)
const deletingId = ref<string | null>(null)
const deleteLoading = ref(false)

onMounted(async () => {
  if (!shopStore.shop) {
    await shopStore.fetchShop()
  }
  if (!shopStore.hasShop) {
    await navigateTo(localePath('/dashboard/shop/create'))
    return
  }
  await loadServices()
})

async function loadServices() {
  isLoadingServices.value = true
  try {
    const response = await shopApi.fetchServices(includeInactive.value)
    services.value = response.services
  } catch {
    toast.error('shop.error.shopNotFound')
  } finally {
    isLoadingServices.value = false
  }
}

watch(includeInactive, () => loadServices())

function openCreateModal() {
  editingService.value = undefined
  modalOpen.value = true
}

function openEditModal(service: ShopService) {
  editingService.value = service
  modalOpen.value = true
}

async function onModalSubmit(data: Record<string, unknown>) {
  modalLoading.value = true
  try {
    if (editingService.value) {
      await shopApi.updateService(editingService.value.id, data as any)
      toast.success('shop.services.updated')
    } else {
      await shopApi.createService(data as any)
      toast.success('shop.services.created')
    }
    modalOpen.value = false
    await loadServices()
  } catch (err) {
    const { error, fieldErrors } = parseApiError(err)
    if (fieldErrors) {
      for (const [field, message] of Object.entries(fieldErrors)) {
        modalRef.value?.setError(field, message)
      }
    } else {
      modalRef.value?.setError('_general', error)
    }
  } finally {
    modalLoading.value = false
  }
}

async function activateService(serviceId: string) {
  try {
    await shopApi.updateService(serviceId, { isActive: true })
    toast.success('shop.services.activated')
    await loadServices()
  } catch {
    toast.error('shop.error.serviceNotFound')
  }
}

function promptDelete(serviceId: string) {
  deletingId.value = serviceId
  confirmOpen.value = true
}

async function confirmDelete() {
  if (!deletingId.value) return
  deleteLoading.value = true
  try {
    await shopApi.deleteService(deletingId.value)
    toast.success('shop.services.deleted')
    confirmOpen.value = false
    deletingId.value = null
    await loadServices()
  } catch {
    toast.error('shop.error.serviceNotFound')
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <div>
    <DashboardPageHeader :title="t('shop.services.title')">
      <template #actions>
        <NuxtLink :to="localePath('/dashboard/shop')">
          <UiButton variant="ghost">{{ t('common.back') }}</UiButton>
        </NuxtLink>
        <UiButton @click="openCreateModal">{{ t('shop.services.add') }}</UiButton>
      </template>
    </DashboardPageHeader>

    <div class="mb-4">
      <UiToggle
        v-model="includeInactive"
        :label="t('shop.services.showInactive')"
      />
    </div>

    <div v-if="isLoadingServices" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-700" />
    </div>

    <div v-else-if="services.length === 0">
      <UiEmptyState
        :title="t('shop.services.empty')"
        :description="t('shop.services.emptyDescription')"
      >
        <template #action>
          <UiButton @click="openCreateModal">{{ t('shop.services.add') }}</UiButton>
        </template>
      </UiEmptyState>
    </div>

    <div v-else class="space-y-3">
      <ShopServiceCard
        v-for="service in services"
        :key="service.id"
        :service="service"
        @edit="openEditModal"
        @delete="promptDelete"
        @activate="activateService"
      />
    </div>

    <ShopServiceFormModal
      ref="modalRef"
      :open="modalOpen"
      :service="editingService"
      :loading="modalLoading"
      @close="modalOpen = false"
      @submit="onModalSubmit"
    />

    <UiConfirmDialog
      :open="confirmOpen"
      :title="t('shop.services.deleteConfirm')"
      :description="t('shop.services.deleteDescription')"
      :loading="deleteLoading"
      @confirm="confirmDelete"
      @cancel="confirmOpen = false"
    />
  </div>
</template>