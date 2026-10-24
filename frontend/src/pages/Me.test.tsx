import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router';
import Me from './Me';
import type { AuthContextType } from '../types/AuthContext';

let mockAuth: Partial<AuthContextType>;

vi.mock('../hooks/useAuth', () => ({
  useAuth: () => mockAuth,
}));

function renderMe() {
  return render(
    <MemoryRouter initialEntries={['/me']}>
      <Routes>
        <Route path="/me" element={<Me />} />
        <Route path="/login" element={<p>Login page</p>} />
      </Routes>
    </MemoryRouter>
  );
}

describe('Me page', () => {
  beforeEach(() => {
    mockAuth = { user: null, isAuthenticated: false, isLoading: false };
  });

  it('shows a loader while the session is loading', () => {
    mockAuth.isLoading = true;
    renderMe();

    expect(screen.getByText('Chargement...')).toBeInTheDocument();
  });

  it('redirects anonymous visitors to /login', () => {
    renderMe();

    expect(screen.getByText('Login page')).toBeInTheDocument();
  });

  it('shows the user email and roles', () => {
    mockAuth = {
      user: { id: 1, email: 'admin@example.com', roles: ['ROLE_ADMIN', 'ROLE_USER'] },
      isAuthenticated: true,
      isLoading: false,
    };
    renderMe();

    expect(screen.getByText('admin@example.com')).toBeInTheDocument();
    expect(screen.getByText('ROLE_ADMIN')).toBeInTheDocument();
    expect(screen.getByText('ROLE_USER')).toBeInTheDocument();
  });
});
