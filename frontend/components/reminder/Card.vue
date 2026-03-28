<script setup lang="ts">
import type { ReminderCandidate } from '~/types/reminder'

const props = defineProps<{
  candidate: ReminderCandidate
}>()

const emit = defineEmits<{
  markReminded: [clientId: string]
}>()

const { t } = useI18n()
const { formatDate } = useFormatters()
const toast = useToast()
const isMarking = ref(false)

async function copyMessage() {
  try {
    await navigator.clipboard.writeText(props.candidate.message)
    toast.success('reminders.toast.copied')
  } catch {
    // Fallback for older browsers
    if (typeof document !== 'undefined') {
      const textarea = document.createElement('textarea')
      textarea.value = props.candidate.message
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
      toast.success('reminders.toast.copied')
    }
  }
}

function zaloLink() {
  const phone = props.candidate.clientPhone.replace(/\D/g, '')
  return `https://zalo.me/${phone}`
}

function phoneLink() {
  return `tel:${props.candidate.clientPhone}`
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <h3 class="text-sm font-medium text-gray-900">{{ candidate.clientName }}</h3>
        <p class="mt-0.5 text-sm text-gray-500">{{ candidate.clientPhone }}</p>
      </div>
      <div class="flex shrink-0 flex-col items-end gap-1">
        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
          {{ t('reminders.card.daysSince', { days: candidate.daysSinceVisit }) }}
        </span>
        <span class="text-xs text-gray-400">
          {{ formatDate(candidate.lastVisitAt) }}
        </span>
      </div>
    </div>

    <div class="mt-3">
      <textarea
        readonly
        :value="candidate.message"
        class="w-full resize-none rounded-md border border-gray-200 bg-gray-50 p-2 text-sm text-gray-700"
        rows="3"
      />
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100"
        :aria-label="t('reminders.card.copyMessage')"
        @click="copyMessage"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        {{ t('reminders.card.copyMessage') }}
      </button>

      <a
        :href="zaloLink()"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100"
        :aria-label="t('reminders.card.zaloLink')"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Zalo
      </a>

      <a
        :href="phoneLink()"
        class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100"
        :aria-label="t('reminders.card.callClient')"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
        {{ t('reminders.card.callClient') }}
      </a>

      <button
        type="button"
        class="ml-auto inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 disabled:opacity-50"
        :disabled="isMarking"
        @click="isMarking = true; emit('markReminded', candidate.clientId)"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        {{ t('reminders.card.markReminded') }}
      </button>
    </div>
  </div>
</template>
