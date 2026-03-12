<script setup lang="ts">
const { t } = useI18n()
const { logout } = useAuth()
const store = useAuthStore()

const isLoggingOut = ref(false)

async function onLogout() {
  isLoggingOut.value = true
  await logout()
}
</script>

<template>
  <div class="flex items-center gap-3">
    <img
      v-if="store.user?.avatarUrl"
      :src="store.user.avatarUrl"
      :alt="store.fullName"
      class="h-9 w-9 rounded-full object-cover"
      referrerpolicy="no-referrer"
    >
    <div
      v-else
      class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-medium text-primary-700"
    >
      {{ store.userInitials }}
    </div>
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-medium text-gray-900">{{ store.fullName }}</p>
      <button
        type="button"
        class="text-sm text-gray-500 hover:text-gray-700"
        :disabled="isLoggingOut"
        @click="onLogout"
      >
        {{ t('auth.logout') }}
      </button>
    </div>
  </div>
</template>