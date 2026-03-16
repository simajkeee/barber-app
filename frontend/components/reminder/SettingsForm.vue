<script setup lang="ts">
import type { ReminderSettings, UpdateReminderSettingsRequest } from '~/types/reminder'
import { reminderSettingsSchema } from '~/schemas/reminder'
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'

const props = defineProps<{
  settings: ReminderSettings
  isLoading: boolean
}>()

const emit = defineEmits<{
  save: [data: UpdateReminderSettingsRequest]
}>()

const { t } = useI18n()

const { handleSubmit, defineField, errors } = useForm({
  validationSchema: toTypedSchema(reminderSettingsSchema),
  initialValues: {
    daysSinceLastVisit: props.settings.daysSinceLastVisit,
    messageTemplate: props.settings.messageTemplate,
  },
})

const [daysSinceLastVisit, daysSinceLastVisitAttrs] = defineField('daysSinceLastVisit')
const [messageTemplate, messageTemplateAttrs] = defineField('messageTemplate')

const onSubmit = handleSubmit((values) => {
  emit('save', values)
})
</script>

<template>
  <form class="max-w-lg space-y-6" @submit.prevent="onSubmit">
    <div>
      <UiInput
        v-model="daysSinceLastVisit"
        v-bind="daysSinceLastVisitAttrs"
        :label="t('reminders.settings.daysSinceLastVisit')"
        type="number"
        :min="1"
        :max="365"
        :error="errors.daysSinceLastVisit"
      />
    </div>

    <div>
      <UiTextarea
        v-model="messageTemplate"
        v-bind="messageTemplateAttrs"
        :label="t('reminders.settings.messageTemplate')"
        :rows="4"
        :maxlength="1000"
        :error="errors.messageTemplate"
      />
      <p class="mt-1 text-xs text-gray-500">
        {{ t('reminders.settings.templateHelp', {
          client_name: '{client_name}',
          shop_name: '{shop_name}',
          days_since_visit: '{days_since_visit}',
          client_phone: '{client_phone}',
        }) }}
      </p>
    </div>

    <UiButton type="submit" :disabled="isLoading">
      {{ t('reminders.settings.save') }}
    </UiButton>
  </form>
</template>
