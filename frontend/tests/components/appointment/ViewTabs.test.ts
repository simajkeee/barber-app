import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ViewTabs from '~/components/appointment/ViewTabs.vue'

function mountTabs(tab: 'daily' | 'list' = 'daily') {
  return mount(ViewTabs, { props: { tab } })
}

describe('AppointmentViewTabs', () => {
  it('renders two tab buttons', () => {
    const wrapper = mountTabs()
    expect(wrapper.findAll('button')).toHaveLength(2)
  })

  it('shows daily and list tab labels', () => {
    const wrapper = mountTabs()
    const text = wrapper.text()
    expect(text).toContain('appointments.tabs.daily')
    expect(text).toContain('appointments.tabs.list')
  })

  it('emits update:tab with "daily" when daily tab clicked', async () => {
    const wrapper = mountTabs('list')
    await wrapper.findAll('button')[0].trigger('click')
    expect(wrapper.emitted('update:tab')![0][0]).toBe('daily')
  })

  it('emits update:tab with "list" when list tab clicked', async () => {
    const wrapper = mountTabs('daily')
    await wrapper.findAll('button')[1].trigger('click')
    expect(wrapper.emitted('update:tab')![0][0]).toBe('list')
  })
})
