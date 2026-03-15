import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReminderList from '~/components/reminder/List.vue'
import { createReminderCandidate } from '~/tests/factories'

describe('ReminderList', () => {
  function mountList(props: { candidates?: any[]; isLoading?: boolean } = {}) {
    return mount(ReminderList, {
      props: {
        candidates: props.candidates ?? [],
        isLoading: props.isLoading ?? false,
      },
      global: {
        stubs: {
          ReminderCard: {
            template: '<div class="reminder-card" />',
            props: ['candidate'],
          },
          UiEmptyState: {
            template: '<div class="empty-state" />',
            props: ['title', 'description'],
          },
        },
      },
    })
  }

  it('shows loading skeletons when loading', () => {
    const wrapper = mountList({ isLoading: true })
    expect(wrapper.findAll('.animate-pulse').length).toBe(3)
  })

  it('shows empty state when no candidates', () => {
    const wrapper = mountList({ candidates: [], isLoading: false })
    expect(wrapper.find('.empty-state').exists()).toBe(true)
  })

  it('renders reminder cards for each candidate', () => {
    const candidates = [
      createReminderCandidate({ clientId: '1' }),
      createReminderCandidate({ clientId: '2' }),
    ]
    const wrapper = mountList({ candidates })
    expect(wrapper.findAll('.reminder-card').length).toBe(2)
  })

  it('does not show empty state when candidates exist', () => {
    const wrapper = mountList({ candidates: [createReminderCandidate()] })
    expect(wrapper.find('.empty-state').exists()).toBe(false)
  })

  it('does not show skeletons when not loading', () => {
    const wrapper = mountList({ candidates: [createReminderCandidate()], isLoading: false })
    expect(wrapper.findAll('.animate-pulse').length).toBe(0)
  })
})
