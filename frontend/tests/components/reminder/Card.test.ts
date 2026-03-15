import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ReminderCard from '~/components/reminder/Card.vue'
import { createReminderCandidate } from '~/tests/factories'

describe('ReminderCard', () => {
  function mountCard(candidate = createReminderCandidate()) {
    return mount(ReminderCard, {
      props: { candidate },
    })
  }

  it('displays client name and phone', () => {
    const wrapper = mountCard()
    expect(wrapper.text()).toContain('Nguyen Van A')
    expect(wrapper.text()).toContain('+84901234567')
  })

  it('displays days since visit', () => {
    const wrapper = mountCard()
    expect(wrapper.text()).toContain('reminders.card.daysSince')
  })

  it('displays message in textarea', () => {
    const candidate = createReminderCandidate({ message: 'Test message content' })
    const wrapper = mountCard(candidate)
    const textarea = wrapper.find('textarea')
    expect(textarea.exists()).toBe(true)
    expect(textarea.element.value).toBe('Test message content')
  })

  it('textarea is readonly', () => {
    const wrapper = mountCard()
    const textarea = wrapper.find('textarea')
    expect(textarea.attributes('readonly')).toBeDefined()
  })

  it('has copy, zalo, call, and mark-reminded buttons', () => {
    const wrapper = mountCard()
    const buttons = wrapper.findAll('button')
    const links = wrapper.findAll('a')

    // Copy button + Mark reminded button
    expect(buttons.length).toBeGreaterThanOrEqual(2)
    // Zalo link + Phone link
    expect(links.length).toBe(2)
  })

  it('emits markReminded with clientId when mark button clicked', async () => {
    const candidate = createReminderCandidate({ clientId: 'test-client-id' })
    const wrapper = mountCard(candidate)

    const markButton = wrapper.findAll('button').find((b) => b.text().includes('reminders.card.markReminded'))
    expect(markButton).toBeTruthy()
    await markButton!.trigger('click')

    expect(wrapper.emitted('markReminded')).toBeTruthy()
    expect(wrapper.emitted('markReminded')![0]).toEqual(['test-client-id'])
  })

  it('zalo link points to correct URL', () => {
    const candidate = createReminderCandidate({ clientPhone: '+84901234567' })
    const wrapper = mountCard(candidate)
    const zaloLink = wrapper.findAll('a').find((a) => a.text().includes('Zalo'))
    expect(zaloLink?.attributes('href')).toBe('https://zalo.me/84901234567')
  })

  it('phone link uses tel: protocol', () => {
    const candidate = createReminderCandidate({ clientPhone: '+84901234567' })
    const wrapper = mountCard(candidate)
    const phoneLink = wrapper.findAll('a').find((a) => a.text().includes('reminders.card.callClient'))
    expect(phoneLink?.attributes('href')).toBe('tel:+84901234567')
  })
})
