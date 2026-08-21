import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { useContext } from 'react';
import axios from 'axios';
import { AuthContext, AuthProvider } from './AuthContext';
import api from '../lib/axios';
import { getUserFromToken } from '../lib/jwt';

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

vi.mock('../lib/axios', () => ({
  __esModule: true,
  default: { post: vi.fn() },
  setAccessToken: vi.fn(),
  getAccessToken: vi.fn(),
}));

vi.mock('../lib/jwt', () => ({
  getUserFromToken: vi.fn(),
}));

function Consumer() {
  const ctx = useContext(AuthContext);
  if (!ctx) return null;

  return (
    <div>
      <span data-testid="loading">{String(ctx.isLoading)}</span>
      <span data-testid="authenticated">{String(ctx.isAuthenticated)}</span>
      <span data-testid="email">{ctx.user?.email ?? ''}</span>
      <button onClick={() => ctx.login('user@example.com', 'password123')}>login</button>
      <button onClick={() => ctx.logout()}>logout</button>
    </div>
  );
}

describe('AuthProvider', () => {
  beforeEach(() => {
    vi.mocked(axios.post).mockReset();
    vi.mocked(api.post).mockReset();
    vi.mocked(getUserFromToken).mockReset();
  });

  it('restores the session from the refresh cookie on mount', async () => {
    vi.mocked(axios.post).mockResolvedValue({ data: { token: 'refreshed-token' } });
    vi.mocked(getUserFromToken).mockReturnValue({ id: 1, email: 'user@example.com', roles: ['ROLE_USER'] });

    render(
      <AuthProvider>
        <Consumer />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId('loading').textContent).toBe('false'));

    expect(screen.getByTestId('authenticated').textContent).toBe('true');
    expect(screen.getByTestId('email').textContent).toBe('user@example.com');
  });

  it('stays logged out when there is no valid refresh session', async () => {
    vi.mocked(axios.post).mockRejectedValue(new Error('no session'));

    render(
      <AuthProvider>
        <Consumer />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId('loading').textContent).toBe('false'));

    expect(screen.getByTestId('authenticated').textContent).toBe('false');
  });

  it('login() authenticates the user via /login_check', async () => {
    vi.mocked(axios.post).mockRejectedValue(new Error('no session'));
    vi.mocked(api.post).mockResolvedValue({ data: { token: 'login-token' } });
    vi.mocked(getUserFromToken).mockReturnValue({ id: 2, email: 'user@example.com', roles: ['ROLE_USER'] });

    render(
      <AuthProvider>
        <Consumer />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId('loading').textContent).toBe('false'));

    fireEvent.click(screen.getByText('login'));

    await waitFor(() => expect(screen.getByTestId('authenticated').textContent).toBe('true'));
    expect(api.post).toHaveBeenCalledWith('/login_check', {
      email: 'user@example.com',
      password: 'password123',
    });
  });

  it('logout() clears the session even if the invalidate call fails', async () => {
    vi.mocked(axios.post)
      .mockResolvedValueOnce({ data: { token: 'refreshed-token' } })
      .mockRejectedValueOnce(new Error('invalidate failed'));
    vi.mocked(getUserFromToken).mockReturnValue({ id: 1, email: 'user@example.com', roles: ['ROLE_USER'] });

    render(
      <AuthProvider>
        <Consumer />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId('authenticated').textContent).toBe('true'));

    fireEvent.click(screen.getByText('logout'));

    await waitFor(() => expect(screen.getByTestId('authenticated').textContent).toBe('false'));
    expect(screen.getByTestId('email').textContent).toBe('');
  });
});
