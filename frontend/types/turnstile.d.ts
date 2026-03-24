interface TurnstileWidget {
  render: (
    container: string | HTMLElement,
    options: {
      sitekey: string
      callback: (token: string) => void
      theme?: 'light' | 'dark' | 'auto'
    },
  ) => string
  reset: (widgetId: string) => void
  remove: (widgetId: string) => void
}

declare global {
  interface Window {
    turnstile?: TurnstileWidget
    onTurnstileLoad?: () => void
  }
}

export {}
