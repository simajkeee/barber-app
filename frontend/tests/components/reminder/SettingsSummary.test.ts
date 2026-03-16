import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReminderSettingsSummary from '~/components/reminder/SettingsSummary.vue'
import { createReminderSettings } from '~/tests/factories'

describe('ReminderSettingsSummary', () => {
  function mountSummary(settings = createReminderSettings()) {
    return mount(ReminderSettingsSummary, {
      props: { settings },
      global: {
        stubs: {
          NuxtLink: { template: '<a :href="to"><slot /></a>', props: ['to'] },
        },
      },
    })
  }

  it('renders the days threshold summary', () => {
    const wrapper = mountSummary(createReminderSettings({ daysSinceLastVisit: 30 }))
    expect(wrapper.text()).toContain('reminders.settings.summary')
  })

  it('renders the message template', () => {
    const settings = createReminderSettings({ messageTemplate: 'Hello {client_name}!' })
    const wrapper = mountSummary(settings)
    expect(wrapper.text()).toContain('Hello {client_name}!')
  })

  it('has a link to the settings page', () => {
    const wrapper = mountSummary()
    const link = wrapper.find('a')
    expect(link.exists()).toBe(true)
    expect(link.text()).toContain('reminders.settings.title')
  })

  it('template text is truncated with CSS (has truncate class)', () => {
    const wrapper = mountSummary()
    const truncated = wrapper.find('.truncate')
    expect(truncated.exists()).toBe(true)
  })

  it('template element has title attribute for full text on hover', () => {
    const settings = createReminderSettings({ messageTemplate: 'Full message template text' })
    const wrapper = mountSummary(settings)
    const truncated = wrapper.find('.truncate')
    expect(truncated.attributes('title')).toBe('Full message template text')
  })
})
