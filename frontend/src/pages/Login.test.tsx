import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import Login from './Login';

const mockNavigate = vi.fn();
const mockLogin = vi.fn();
const mockToastSuccess = vi.fn();
const mockToastError = vi.fn();

vi.mock('react-router', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router')>();
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

vi.mock('../hooks/useAuth', () => ({
  useAuth: () => ({ login: mockLogin }),
}));

vi.mock('../hooks/useToast', () => ({
  useToast: () => ({ success: mockToastSuccess, error: mockToastError }),
}));

function renderLogin() {
  return render(
    <MemoryRouter>
      <Login />
    </MemoryRouter>
  );
}

describe('Login page', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    mockLogin.mockReset();
    mockToastSuccess.mockReset();
    mockToastError.mockReset();
  });

  it('renders the email and password fields with a submit button', () => {
    renderLogin();

    expect(screen.getByLabelText('Email:')).toBeInTheDocument();
    expect(screen.getByLabelText('Mot de passe:')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /se connecter/i })).toBeInTheDocument();
  });

  it('shows validation errors and does not call login on empty submit', async () => {
    renderLogin();

    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    expect(await screen.findByText('Email invalide')).toBeInTheDocument();
    expect(mockLogin).not.toHaveBeenCalled();
  });

  it('logs in, shows a success toast and navigates home on valid submit', async () => {
    mockLogin.mockResolvedValue(undefined);
    renderLogin();

    fireEvent.change(screen.getByLabelText('Email:'), { target: { value: 'user@example.com' } });
    fireEvent.change(screen.getByLabelText('Mot de passe:'), { target: { value: 'password123' } });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => expect(mockLogin).toHaveBeenCalledWith('user@example.com', 'password123'));
    await waitFor(() => expect(mockNavigate).toHaveBeenCalledWith('/'));
    expect(mockToastSuccess).toHaveBeenCalledWith('Connexion réussie !');
  });

  it('shows the server error message and an error toast when login fails', async () => {
    mockLogin.mockRejectedValue({ response: { data: { error: 'Identifiants invalides' } } });
    renderLogin();

    fireEvent.change(screen.getByLabelText('Email:'), { target: { value: 'user@example.com' } });
    fireEvent.change(screen.getByLabelText('Mot de passe:'), { target: { value: 'wrong-password' } });
    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    expect(await screen.findByText('Identifiants invalides')).toBeInTheDocument();
    expect(mockToastError).toHaveBeenCalledWith('Identifiants invalides');
    expect(mockNavigate).not.toHaveBeenCalled();
  });
});
