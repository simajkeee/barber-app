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

const automatedEmailEnabled = ref(props.settings.automatedEmailEnabled)

const onSubmit = handleSubmit((values) => {
  emit('save', { ...values, automatedEmailEnabled: automatedEmailEnabled.value })
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

    <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
      <button
        type="button"
        role="switch"
        :aria-checked="automatedEmailEnabled"
        :aria-label="t('reminders.settings.automatedEmailLabel')"
        class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        :class="automatedEmailEnabled ? 'bg-primary-600' : 'bg-gray-200'"
        @click="automatedEmailEnabled = !automatedEmailEnabled"
      >
        <span
          class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform"
          :class="automatedEmailEnabled ? 'translate-x-5' : 'translate-x-0'"
        />
      </button>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-900">{{ t('reminders.settings.automatedEmailLabel') }}</p>
        <p class="mt-0.5 text-xs text-gray-500">{{ t('reminders.settings.automatedEmailDescription') }}</p>
      </div>
    </div>

    <UiButton type="submit" :disabled="isLoading">
      {{ t('reminders.settings.save') }}
    </UiButton>
  </form>
</template>
