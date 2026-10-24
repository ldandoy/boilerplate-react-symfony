import { describe, it, expect } from 'vitest';
import { getApiErrorData, getApiErrorMessage } from './apiError';

describe('getApiErrorData', () => {
  it('returns the response data of an HTTP error', () => {
    const data = { error: 'Oops' };

    expect(getApiErrorData({ response: { data } })).toBe(data);
  });

  it('returns undefined for errors without a response', () => {
    expect(getApiErrorData(new Error('Network Error'))).toBeUndefined();
    expect(getApiErrorData(null)).toBeUndefined();
    expect(getApiErrorData(undefined)).toBeUndefined();
  });
});

describe('getApiErrorMessage', () => {
  it('prefers the "error" field', () => {
    const error = { response: { data: { error: 'Error', message: 'Message', detail: 'Detail' } } };

    expect(getApiErrorMessage(error, 'Fallback')).toBe('Error');
  });

  it('falls back to the "message" field (lexik JWT errors)', () => {
    const error = { response: { data: { code: 401, message: 'Invalid credentials.' } } };

    expect(getApiErrorMessage(error, 'Fallback')).toBe('Invalid credentials.');
  });

  it('falls back to the "detail" field (API Platform problem+json)', () => {
    const error = { response: { data: { detail: 'email: There is already an account with this email' } } };

    expect(getApiErrorMessage(error, 'Fallback')).toBe('email: There is already an account with this email');
  });

  it('returns the fallback when nothing usable is provided', () => {
    expect(getApiErrorMessage({ response: { data: {} } }, 'Fallback')).toBe('Fallback');
    expect(getApiErrorMessage(new Error('Network Error'), 'Fallback')).toBe('Fallback');
  });
});
