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

    const data = err.data as Record<string, unknown>
    const code = typeof data.code === 'string' ? data.code : undefined
    const message = typeof data.message === 'string' ? data.message : 'unexpected'
    const details = Array.isArray(data.details) ? data.details as { field: string; message: string }[] : []
    const fieldErrors: Record<string, string> | undefined = details.length
      ? Object.fromEntries(details.map((d) => [d.field, d.message]))
      : undefined

    return { error: code ?? message, fieldErrors }
  }

  return { parseApiError }
}