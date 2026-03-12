<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { createShopSchema, updateShopSchema } from '~/schemas/shop'
import type { Shop } from '~/types/shop'

const props = withDefaults(
  defineProps<{
    shop?: Shop
    loading?: boolean
  }>(),
  { shop: undefined, loading: false },
)

const emit = defineEmits<{
  submit: [data: Record<string, unknown>]
  cancel: []
}>()

const { t } = useI18n()

const isEditMode = computed(() => !!props.shop)

const { handleSubmit, setFieldError, isSubmitting } = useForm({
  validationSchema: toTypedSchema(isEditMode.value ? updateShopSchema : createShopSchema),
  initialValues: {
    name: props.shop?.name ?? '',
    address: props.shop?.address ?? '',
    phone: props.shop?.phone ?? '',
    description: props.shop?.description ?? '',
    ...(isEditMode.value ? {
      slug: props.shop?.slug ?? '',
      coverImageUrl: props.shop?.coverImageUrl ?? '',
    } : {}),
  },
})

const { value: name, errorMessage: nameError } = useField<string>('name')
const { value: address, errorMessage: addressError } = useField<string>('address')
const { value: phone, errorMessage: phoneError } = useField<string>('phone')
const { value: description, errorMessage: descriptionError } = useField<string>('description')
const { value: slug, errorMessage: slugError } = useField<string>('slug')
const { value: coverImageUrl, errorMessage: coverImageUrlError } = useField<string>('coverImageUrl')

const generalError = ref<string | null>(null)

const onSubmit = handleSubmit((values) => {
  generalError.value = null
  emit('submit', values)
})

function setError(field: string, message: string) {
  if (field === '_general') {
    generalError.value = message
  } else {
    setFieldError(field, message)
  }
}

defineExpose({ setError })
</script>

<template>
  <form @submit="onSubmit" novalidate>
    <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

    <div class="space-y-4">
      <UiInput
        v-model="name"
        :label="t('shop.form.name')"
        required
        :error="nameError"
      />

      <UiInput
        v-model="address"
        :label="t('shop.form.address')"
        required
        :error="addressError"
      />

      <UiInput
        v-model="phone"
        :label="t('shop.form.phone')"
        required
        :error="phoneError"
      />

      <UiTextarea
        v-model="description"
        :label="t('shop.profile.description')"
        :rows="3"
        :error="descriptionError"
      />

      <template v-if="isEditMode">
        <div>
          <UiInput
            v-model="slug"
            :label="t('shop.profile.slug')"
            :error="slugError"
          />
          <p class="mt-1 text-xs text-gray-500">{{ t('shop.profile.slugHint') }}</p>
        </div>

        <div>
          <UiInput
            v-model="coverImageUrl"
            :label="t('shop.profile.coverImageUrl')"
            :error="coverImageUrlError"
          />
          <div v-if="coverImageUrl" class="mt-2">
            <img
              :src="coverImageUrl"
              alt=""
              class="h-32 w-full rounded-lg border object-cover"
              @error="($event.target as HTMLImageElement).style.display = 'none'"
            />
          </div>
        </div>
      </template>
    </div>

    <div class="mt-6 flex gap-3">
      <UiButton v-if="isEditMode" variant="secondary" @click="emit('cancel')">
        {{ t('shop.profile.cancel') }}
      </UiButton>
      <UiButton type="submit" :loading="loading || isSubmitting">
        {{ isEditMode ? t('shop.profile.save') : t('shop.create.title') }}
      </UiButton>
    </div>
  </form>
</template>