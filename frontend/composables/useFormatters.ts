export function useFormatters() {
  const { locale, t } = useI18n()

  function formatPrice(price: number): string {
    return new Intl.NumberFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
      style: 'currency',
      currency: 'VND',
      maximumFractionDigits: 0,
    }).format(price)
  }

  function formatDuration(minutes: number): string {
    return t('shop.services.minutes', { n: minutes })
  }

  function formatDate(isoString: string): string {
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(new Date(isoString))
  }

  return { formatPrice, formatDuration, formatDate }
}