import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import axios from 'axios';

vi.mock('axios', async (importOriginal) => {
  const actual = await importOriginal<typeof import('axios')>();
  return {
    ...actual,
    default: {
      ...actual.default,
      post: vi.fn(),
    },
  };
});

import api, { setAccessToken, getAccessToken } from './axios';

// axios doesn't expose interceptor handlers publicly; reaching into the
// internal array is the standard way to unit-test interceptor logic
// without spinning up a real HTTP server.
type InterceptorHandlers<F, R> = { handlers: Array<{ fulfilled: F; rejected: R }> };
type RequestHandlers = InterceptorHandlers<(config: object) => { headers: Record<string, string> }, unknown>;
type ResponseHandlers = InterceptorHandlers<unknown, (error: unknown) => Promise<unknown>>;

function getRequestFulfilled() {
  return (api.interceptors.request as unknown as RequestHandlers).handlers[0].fulfilled;
}

function getResponseRejected() {
  return (api.interceptors.response as unknown as ResponseHandlers).handlers[0].rejected;
}

describe('axios request interceptor', () => {
  afterEach(() => {
    setAccessToken(null);
  });

  it('attaches the Authorization header when a token is set', () => {
    setAccessToken('my-token');
    const fulfilled = getRequestFulfilled();

    const config = fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBe('Bearer my-token');
  });

  it('does not attach an Authorization header when no token is set', () => {
    setAccessToken(null);
    const fulfilled = getRequestFulfilled();

    const config = fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBeUndefined();
  });
});

describe('axios response interceptor (401 refresh flow)', () => {
  beforeEach(() => {
    vi.mocked(axios.post).mockReset();
    setAccessToken(null);
  });

  it('refreshes the token, updates the original request header and marks it as retried', async () => {
    vi.mocked(axios.post).mockResolvedValue({ data: { token: 'new-token' } });

    const rejected = getResponseRejected();
    const originalRequest = { headers: {} as Record<string, string>, _retry: false };
    const error = { response: { status: 401 }, config: originalRequest };

    // The interceptor retries via `api(originalRequest)`, a real HTTP call with
    // no server behind it in this test; we only care about the refresh/header
    // logic that runs before that, so the eventual network failure is ignored.
    await rejected(error).catch(() => undefined);

    expect(axios.post).toHaveBeenCalledWith(
      expect.stringContaining('/token/refresh'),
      {},
      { withCredentials: true }
    );
    expect(getAccessToken()).toBe('new-token');
    expect(originalRequest.headers.Authorization).toBe('Bearer new-token');
    expect(originalRequest._retry).toBe(true);
  });

  it('clears the access token and rejects when the refresh call fails', async () => {
    setAccessToken('stale-token');
    vi.mocked(axios.post).mockRejectedValue(new Error('refresh failed'));

    const rejected = getResponseRejected();
    const originalRequest = { headers: {}, _retry: false };
    const error = { response: { status: 401 }, config: originalRequest };

    await expect(rejected(error)).rejects.toThrow('refresh failed');
    expect(getAccessToken()).toBeNull();
  });

  it('propagates non-401 errors without attempting a refresh', async () => {
    const rejected = getResponseRejected();
    const originalRequest = { headers: {}, _retry: false };
    const error = { response: { status: 500 }, config: originalRequest };

    await expect(rejected(error)).rejects.toBe(error);
    expect(axios.post).not.toHaveBeenCalled();
  });
});
