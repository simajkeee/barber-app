<script setup lang="ts">
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { createServiceSchema, updateServiceSchema } from '~/schemas/shop'
import type { ShopService } from '~/types/shop'

const props = withDefaults(
  defineProps<{
    service?: ShopService
    open: boolean
    loading?: boolean
  }>(),
  { service: undefined, loading: false },
)

const emit = defineEmits<{
  close: []
  submit: [data: Record<string, unknown>]
}>()

const { t } = useI18n()

const isEditMode = computed(() => !!props.service)

const { handleSubmit, setFieldError, resetForm } = useForm({
  validationSchema: toTypedSchema(isEditMode.value ? updateServiceSchema : createServiceSchema),
  initialValues: {
    name: props.service?.name ?? '',
    durationMinutes: props.service?.durationMinutes ?? 30,
    price: props.service?.price ?? 0,
    sortOrder: props.service?.sortOrder ?? 0,
  },
})

const { value: name, errorMessage: nameError } = useField<string>('name')
const { value: durationMinutes, errorMessage: durationError } = useField<number>('durationMinutes')
const { value: price, errorMessage: priceError } = useField<number>('price')
const { value: sortOrder, errorMessage: sortOrderError } = useField<number>('sortOrder')

const generalError = ref<string | null>(null)

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    generalError.value = null
    resetForm({
      values: {
        name: props.service?.name ?? '',
        durationMinutes: props.service?.durationMinutes ?? 30,
        price: props.service?.price ?? 0,
        sortOrder: props.service?.sortOrder ?? 0,
      },
    })
  }
})

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
  <UiModal
    :open="props.open"
    :title="isEditMode ? t('shop.services.edit') : t('shop.services.add')"
    @close="emit('close')"
  >
    <form @submit="onSubmit" novalidate>
      <UiAlert v-if="generalError" :message="generalError" class="mb-4" />

      <div class="space-y-4">
        <UiInput
          v-model="name"
          :label="t('shop.form.serviceName')"
          required
          :error="nameError"
        />

        <UiInput
          v-model.number="durationMinutes"
          type="text"
          :label="t('shop.form.duration')"
          required
          :error="durationError"
        />

        <UiInput
          v-model.number="price"
          type="text"
          :label="t('shop.form.price')"
          required
          :error="priceError"
        />

        <UiInput
          v-model.number="sortOrder"
          type="text"
          :label="t('shop.form.sortOrder')"
          :error="sortOrderError"
        />
      </div>
    </form>

    <template #footer>
      <div class="flex justify-end gap-3">
        <UiButton variant="secondary" @click="emit('close')">
          {{ t('common.cancel') }}
        </UiButton>
        <UiButton :loading="props.loading" @click="onSubmit">
          {{ t('common.save') }}
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>