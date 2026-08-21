import { describe, it, expect } from 'vitest';
import { decodeJwt, getUserFromToken, isTokenExpired } from './jwt';

function base64url(input: string): string {
  return btoa(input).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function makeToken(payload: Record<string, unknown>): string {
  const header = base64url(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
  const body = base64url(JSON.stringify(payload));
  return `${header}.${body}.fake-signature`;
}

describe('decodeJwt', () => {
  it('returns null for a malformed token', () => {
    expect(decodeJwt('not-a-jwt')).toBeNull();
  });

  it('decodes a valid token payload', () => {
    const token = makeToken({ exp: 9999999999, iat: 1, roles: ['ROLE_USER'], email: 'user@example.com' });
    const payload = decodeJwt(token);

    expect(payload).not.toBeNull();
    expect(payload?.email).toBe('user@example.com');
    expect(payload?.roles).toEqual(['ROLE_USER']);
  });
});

describe('getUserFromToken', () => {
  it('returns null for an invalid token', () => {
    expect(getUserFromToken('invalid')).toBeNull();
  });

  it('builds a User from a token with an email', () => {
    const token = makeToken({ exp: 9999999999, iat: 1, roles: ['ROLE_USER'], id: 7, email: 'user@example.com' });
    const user = getUserFromToken(token);

    expect(user).toEqual({ id: 7, email: 'user@example.com', roles: ['ROLE_USER'] });
  });

  it('falls back to username when email is absent', () => {
    const token = makeToken({ exp: 9999999999, iat: 1, roles: [], username: 'fallback@example.com' });
    const user = getUserFromToken(token);

    expect(user?.email).toBe('fallback@example.com');
  });
});

describe('isTokenExpired', () => {
  it('returns true for an invalid token', () => {
    expect(isTokenExpired('invalid')).toBe(true);
  });

  it('returns true when exp is in the past', () => {
    const token = makeToken({ exp: 1, iat: 0, roles: [] });
    expect(isTokenExpired(token)).toBe(true);
  });

  it('returns false when exp is in the future', () => {
    const token = makeToken({ exp: 9999999999, iat: 0, roles: [] });
    expect(isTokenExpired(token)).toBe(false);
  });
});
