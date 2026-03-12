<script setup lang="ts">
import type { Shop } from '~/types/shop'

defineProps<{
  shop: Shop
}>()

const { t } = useI18n()

const copied = ref(false)

async function copySlug(slug: string) {
  await navigator.clipboard.writeText(slug)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div v-if="shop.coverImageUrl" class="mb-4">
      <img
        :src="shop.coverImageUrl"
        alt=""
        class="h-40 w-full rounded-lg object-cover"
        @error="($event.target as HTMLImageElement).style.display = 'none'"
      />
    </div>

    <dl class="space-y-3">
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('shop.form.name') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ shop.name }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('shop.form.address') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ shop.address }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('shop.form.phone') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">{{ shop.phone }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('shop.profile.description') }}</dt>
        <dd class="mt-0.5 text-sm text-gray-900">
          {{ shop.description || t('shop.profile.noDescription') }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase text-gray-500">{{ t('shop.profile.slug') }}</dt>
        <dd class="mt-0.5 flex items-center gap-2 text-sm text-gray-900">
          <code class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ shop.slug }}</code>
          <button
            type="button"
            class="text-xs text-primary-600 hover:text-primary-700"
            @click="copySlug(shop.slug)"
          >
            {{ copied ? t('common.copied') : t('common.copy') }}
          </button>
        </dd>
      </div>
    </dl>
  </div>
</template>