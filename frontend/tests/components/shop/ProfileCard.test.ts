import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ProfileCard from '~/components/shop/ProfileCard.vue'
import { createShop } from '../../factories'

const mockWriteText = vi.fn().mockResolvedValue(undefined)

beforeEach(() => {
  mockWriteText.mockClear()
  Object.defineProperty(navigator, 'clipboard', {
    value: { writeText: mockWriteText },
    writable: true,
    configurable: true,
  })
})

function mountCard(shopOverrides: Record<string, any> = {}) {
  return mount(ProfileCard, {
    props: { shop: createShop(shopOverrides) },
  })
}

describe('ProfileCard', () => {
  describe('rendering', () => {
    it('displays shop name', () => {
      const wrapper = mountCard({ name: 'Cool Cuts' })
      expect(wrapper.text()).toContain('Cool Cuts')
    })

    it('displays shop address', () => {
      const wrapper = mountCard({ address: '456 Elm St' })
      expect(wrapper.text()).toContain('456 Elm St')
    })

    it('displays shop phone', () => {
      const wrapper = mountCard({ phone: '0909090909' })
      expect(wrapper.text()).toContain('0909090909')
    })

    it('displays description when present', () => {
      const wrapper = mountCard({ description: 'Best shop in town' })
      expect(wrapper.text()).toContain('Best shop in town')
    })

    it('shows fallback when no description', () => {
      const wrapper = mountCard({ description: null })
      expect(wrapper.text()).toContain('shop.profile.noDescription')
    })

    it('displays slug in code element', () => {
      const wrapper = mountCard({ slug: 'my-cool-shop' })
      expect(wrapper.find('code').text()).toBe('my-cool-shop')
    })
  })

  describe('cover image', () => {
    it('renders cover image when url present', () => {
      const wrapper = mountCard({ coverImageUrl: 'https://example.com/img.jpg' })
      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('https://example.com/img.jpg')
    })

    it('does not render cover image when null', () => {
      const wrapper = mountCard({ coverImageUrl: null })
      expect(wrapper.find('img').exists()).toBe(false)
    })
  })

  describe('copy slug', () => {
    it('copies slug to clipboard on button click', async () => {
      const wrapper = mountCard({ slug: 'test-slug' })
      await wrapper.find('button').trigger('click')
      expect(mockWriteText).toHaveBeenCalledWith('test-slug')
    })

    it('shows copied text after click', async () => {
      vi.useFakeTimers()
      const wrapper = mountCard({ slug: 'test-slug' })

      expect(wrapper.find('button').text()).toContain('common.copy')
      await wrapper.find('button').trigger('click')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('button').text()).toContain('common.copied')

      vi.advanceTimersByTime(2000)
      await wrapper.vm.$nextTick()
      expect(wrapper.find('button').text()).toContain('common.copy')
      vi.useRealTimers()
    })
  })
})