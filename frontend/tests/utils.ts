import { flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'

export async function flush() {
  await flushPromises()
  await nextTick()
  await flushPromises()
}