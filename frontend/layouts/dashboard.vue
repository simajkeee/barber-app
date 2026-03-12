<script setup lang="ts">
const { t } = useI18n()
const isSidebarOpen = ref(false)
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

      <nav class="flex-1 p-4">
        <!-- Navigation links added by future features -->
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