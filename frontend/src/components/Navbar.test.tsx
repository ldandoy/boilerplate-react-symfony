import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import { Navbar } from './Navbar';
import { ThemeProvider } from '../context/ThemeContext';
import type { AuthContextType } from '../types/AuthContext';

const mockNavigate = vi.fn();
const mockLogout = vi.fn();
let mockAuth: Partial<AuthContextType>;

vi.mock('react-router', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router')>();
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

vi.mock('../hooks/useAuth', () => ({
  useAuth: () => mockAuth,
}));

const user = { id: 1, email: 'user@example.com', roles: ['ROLE_USER'] };

function renderNavbar() {
  return render(
    <ThemeProvider>
      <MemoryRouter>
        <Navbar />
      </MemoryRouter>
    </ThemeProvider>
  );
}

describe('Navbar', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    mockLogout.mockReset();
    mockAuth = { user: null, isAuthenticated: false, isLoading: false, logout: mockLogout };
  });

  it('shows a placeholder while the session is loading', () => {
    mockAuth.isLoading = true;
    renderNavbar();

    expect(screen.getByText('...')).toBeInTheDocument();
    expect(screen.queryByText('Login')).not.toBeInTheDocument();
  });

  it('shows login and register links to anonymous visitors', () => {
    renderNavbar();

    expect(screen.getByRole('link', { name: 'Login' })).toHaveAttribute('href', '/login');
    expect(screen.getByRole('link', { name: 'Register' })).toHaveAttribute('href', '/register');
  });

  describe('when authenticated', () => {
    beforeEach(() => {
      mockAuth = { user, isAuthenticated: true, isLoading: false, logout: mockLogout };
    });

    it('shows the user email and hides the login links', () => {
      renderNavbar();

      expect(screen.getByRole('button', { name: 'user@example.com' })).toBeInTheDocument();
      expect(screen.queryByText('Login')).not.toBeInTheDocument();
      expect(screen.queryByText('Mon profil')).not.toBeInTheDocument();
    });

    it('opens the dropdown and closes it on outside click', () => {
      renderNavbar();

      fireEvent.click(screen.getByRole('button', { name: 'user@example.com' }));
      expect(screen.getByRole('link', { name: 'Mon profil' })).toHaveAttribute('href', '/me');

      fireEvent.mouseDown(document.body);
      expect(screen.queryByText('Mon profil')).not.toBeInTheDocument();
    });

    it('logs out and redirects to /login', async () => {
      mockLogout.mockResolvedValue(undefined);
      renderNavbar();

      fireEvent.click(screen.getByRole('button', { name: 'user@example.com' }));
      fireEvent.click(screen.getByRole('button', { name: 'Déconnexion' }));

      await waitFor(() => expect(mockNavigate).toHaveBeenCalledWith('/login'));
      expect(mockLogout).toHaveBeenCalled();
      expect(screen.queryByText('Mon profil')).not.toBeInTheDocument();
    });
  });
});
