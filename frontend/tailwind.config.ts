import type { Config } from 'tailwindcss'

export default {
  content: [],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f2f7fc',
          100: '#e4eef7',
          200: '#c0d6eb',
          300: '#8fb3d9',
          400: '#5a8abf',
          500: '#3566a0',
          600: '#2a4f7f',
          700: '#1e3a5f',
          800: '#162d4a',
          900: '#0f1f33',
        },
        accent: {
          100: '#fef3c7',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
        },
        success: { DEFAULT: '#16a34a', light: '#dcfce7' },
        warning: { DEFAULT: '#f59e0b', light: '#fef3c7' },
        error: { DEFAULT: '#dc2626', light: '#fee2e2' },
        info: { DEFAULT: '#2563eb', light: '#dbeafe' },
      },
      boxShadow: {
        card: '0 1px 3px rgba(0,0,0,0.08)',
        'card-raised': '0 4px 12px rgba(0,0,0,0.10)',
      },
    },
  },
  plugins: [],
} satisfies Config