import type { ApiValidationError } from '~/types/auth'
import { FetchError } from 'ofetch'

export interface ApiError {
  error: string
  fieldErrors?: Record<string, string>
}

export function useApiError() {
  function parseApiError(err: unknown): ApiError {
    if (!(err instanceof FetchError) || !err.data) {
      return { error: 'unexpected' }
    }

    const data = err.data as ApiValidationError
    const fieldErrors: Record<string, string> | undefined = data.details?.length
      ? Object.fromEntries(data.details.map((d) => [d.field, d.message]))
      : undefined

    return { error: data.code ?? data.message, fieldErrors }
  }

  return { parseApiError }
}