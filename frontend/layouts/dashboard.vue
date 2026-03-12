<script setup lang="ts">
const { t } = useI18n()
const isSidebarOpen = ref(false)
const shopStore = useShopStore()

onMounted(async () => {
  if (!shopStore.shop && !shopStore.isLoading) {
    await shopStore.fetchShop()
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 antialiased lg:flex">
    <!-- Mobile header -->
    <header class="flex items-center justify-between border-b bg-white px-4 py-3 lg:hidden">
      <UiAppLogo size="sm" />
      <button
        type="button"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
        :aria-label="t('dashboard.toggleMenu')"
        @click="isSidebarOpen = !isSidebarOpen"
      >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </header>

    <!-- Sidebar backdrop (mobile) -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
      @click="isSidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-white transition-transform lg:static lg:translate-x-0"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-16 items-center border-b px-6">
        <UiAppLogo />
      </div>

      <nav class="flex-1 space-y-1 p-4">
        <DashboardSidebarLink :to="'/dashboard'" :label="t('nav.dashboard')">
          <template #icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
          </template>
        </DashboardSidebarLink>
        <DashboardSidebarLink v-if="shopStore.hasShop" :to="'/dashboard/shop'" :label="t('nav.shop')">
          <template #icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </template>
        </DashboardSidebarLink>
        <DashboardSidebarLink v-if="shopStore.hasShop" :to="'/dashboard/clients'" :label="t('nav.clients')">
          <template #icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
          </template>
        </DashboardSidebarLink>
        <DashboardSidebarLink v-if="shopStore.hasShop" :to="'/dashboard/shop/services'" :label="t('nav.services')">
          <template #icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243z" /></svg>
          </template>
        </DashboardSidebarLink>
      </nav>

      <div class="border-t p-4">
        <UiLanguageSwitcher />
        <DashboardUserMenu class="mt-3" />
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6 lg:p-8">
      <slot />
    </main>
  </div>
</template>