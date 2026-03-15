<script setup lang="ts">
import type { AppointmentClient } from '~/types/appointment'

const props = withDefaults(
  defineProps<{
    client: AppointmentClient
    linkable?: boolean
  }>(),
  { linkable: false },
)

const localePath = useLocalePath()
</script>

<template>
  <div class="min-w-0">
    <NuxtLink
      v-if="linkable"
      :to="localePath(`/dashboard/clients/${client.id}`)"
      class="text-sm font-medium text-primary-700 hover:underline truncate block"
    >
      {{ client.firstName }} {{ client.lastName }}
    </NuxtLink>
    <p v-else class="text-sm font-medium text-gray-900 truncate">
      {{ client.firstName }} {{ client.lastName }}
    </p>
    <p class="text-xs text-gray-500">{{ client.phone }}</p>
  </div>
</template>
