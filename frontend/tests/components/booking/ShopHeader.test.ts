import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ShopHeader from '~/components/booking/ShopHeader.vue'
import { createPublicShopInfo } from '~/tests/factories'

describe('BookingShopHeader', () => {
  function mountHeader(shop = createPublicShopInfo()) {
    return mount(ShopHeader, { props: { shop } })
  }

  it('displays shop name', () => {
    const wrapper = mountHeader()
    expect(wrapper.text()).toContain('Test Barber')
  })

  it('displays shop address', () => {
    const wrapper = mountHeader()
    expect(wrapper.text()).toContain('123 Nguyen Hue, Q.1')
  })

  it('displays shop phone as tel link', () => {
    const wrapper = mountHeader()
    const link = wrapper.find('a[href="tel:0901234567"]')
    expect(link.exists()).toBe(true)
    expect(link.text()).toBe('0901234567')
  })

  it('does not render address if empty', () => {
    const shop = createPublicShopInfo({ address: '' })
    const wrapper = mountHeader(shop)
    // Address element is conditionally rendered with v-if
    const paragraphs = wrapper.findAll('p')
    const addressPara = paragraphs.find(p => p.text().includes('📍'))
    expect(addressPara).toBeUndefined()
  })

  it('does not render phone if empty', () => {
    const shop = createPublicShopInfo({ phone: '' })
    const wrapper = mountHeader(shop)
    const phoneLink = wrapper.find('a[href^="tel:"]')
    expect(phoneLink.exists()).toBe(false)
  })
})
