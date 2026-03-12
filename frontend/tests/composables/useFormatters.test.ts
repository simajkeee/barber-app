import { describe, it, expect, vi } from 'vitest'
import { ref } from 'vue'

const mockT = vi.fn((key: string, params?: any) => `${key}:${JSON.stringify(params)}`)

vi.stubGlobal('useI18n', () => ({
  locale: ref('vi'),
  t: mockT,
}))

const { useFormatters } = await import('~/composables/useFormatters')

describe('useFormatters', () => {
  describe('formatPrice', () => {
    it('formats price in VND for vi locale', () => {
      const { formatPrice } = useFormatters()
      const result = formatPrice(100000)
      expect(result).toContain('100.000')
    })

    it('formats zero price', () => {
      const { formatPrice } = useFormatters()
      const result = formatPrice(0)
      expect(result).toContain('0')
    })
  })

  describe('formatDuration', () => {
    it('calls t with shop.services.minutes key and n param', () => {
      mockT.mockClear()
      const { formatDuration } = useFormatters()
      formatDuration(30)
      expect(mockT).toHaveBeenCalledWith('shop.services.minutes', { n: 30 })
    })
  })
})