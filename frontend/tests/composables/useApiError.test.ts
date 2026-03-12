import { describe, it, expect } from 'vitest'
import { FetchError } from 'ofetch'
import { useApiError } from '~/composables/useApiError'

function createFetchError(data: any): FetchError {
  const err = new FetchError('Fetch error')
  err.data = data
  return err
}

describe('useApiError', () => {
  const { parseApiError } = useApiError()

  describe('non-FetchError inputs', () => {
    it('returns unexpected for generic Error', () => {
      expect(parseApiError(new Error('oops'))).toEqual({ error: 'unexpected' })
    })

    it('returns unexpected for string', () => {
      expect(parseApiError('something')).toEqual({ error: 'unexpected' })
    })

    it('returns unexpected for null', () => {
      expect(parseApiError(null)).toEqual({ error: 'unexpected' })
    })
  })

  describe('FetchError without data', () => {
    it('returns unexpected when data is undefined', () => {
      const err = new FetchError('Fetch error')
      expect(parseApiError(err)).toEqual({ error: 'unexpected' })
    })
  })

  describe('FetchError with error code', () => {
    it('extracts error code from data', () => {
      const err = createFetchError({ code: 'INVALID_CREDENTIALS', message: 'Bad creds' })
      expect(parseApiError(err)).toEqual({ error: 'INVALID_CREDENTIALS' })
    })

    it('falls back to message when no code', () => {
      const err = createFetchError({ message: 'Something went wrong' })
      expect(parseApiError(err)).toEqual({ error: 'Something went wrong' })
    })
  })

  describe('FetchError with validation details', () => {
    it('maps details array to fieldErrors record', () => {
      const err = createFetchError({
        code: 'VALIDATION_ERROR',
        message: 'Validation failed',
        details: [
          { field: 'email', message: 'Invalid email' },
          { field: 'name', message: 'Required' },
        ],
      })
      const result = parseApiError(err)
      expect(result.error).toBe('VALIDATION_ERROR')
      expect(result.fieldErrors).toEqual({
        email: 'Invalid email',
        name: 'Required',
      })
    })

    it('does not set fieldErrors when details is empty', () => {
      const err = createFetchError({ code: 'SOME_ERROR', details: [] })
      const result = parseApiError(err)
      expect(result.error).toBe('SOME_ERROR')
      expect(result.fieldErrors).toBeUndefined()
    })
  })
})