import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import axios from 'axios';
import Register from './Register';

const mockNavigate = vi.fn();

vi.mock('axios', () => ({
  default: { post: vi.fn() },
}));

vi.mock('react-router', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router')>();
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

function renderRegister() {
  return render(
    <MemoryRouter>
      <Register />
    </MemoryRouter>
  );
}

function fillValidForm() {
  fireEvent.change(screen.getByLabelText('Email:'), { target: { value: 'user@example.com' } });
  fireEvent.change(screen.getByLabelText('Mot de passe:'), { target: { value: 'password123' } });
  fireEvent.change(screen.getByLabelText('Confirmer le mot de passe:'), {
    target: { value: 'password123' },
  });
}

describe('Register page', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    vi.mocked(axios.post).mockReset();
  });

  it('renders email, password and confirmPassword fields', () => {
    renderRegister();

    expect(screen.getByLabelText('Email:')).toBeInTheDocument();
    expect(screen.getByLabelText('Mot de passe:')).toBeInTheDocument();
    expect(screen.getByLabelText('Confirmer le mot de passe:')).toBeInTheDocument();
  });

  it('blocks submission on mismatched passwords without calling the API', async () => {
    renderRegister();

    fireEvent.change(screen.getByLabelText('Email:'), { target: { value: 'user@example.com' } });
    fireEvent.change(screen.getByLabelText('Mot de passe:'), { target: { value: 'password123' } });
    fireEvent.change(screen.getByLabelText('Confirmer le mot de passe:'), {
      target: { value: 'different123' },
    });
    fireEvent.click(screen.getByRole('button', { name: /s'inscrire/i }));

    expect(await screen.findByText('Les mots de passe ne correspondent pas')).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('blocks submission on a too-short password', async () => {
    renderRegister();

    fireEvent.change(screen.getByLabelText('Email:'), { target: { value: 'user@example.com' } });
    fireEvent.change(screen.getByLabelText('Mot de passe:'), { target: { value: 'short1' } });
    fireEvent.change(screen.getByLabelText('Confirmer le mot de passe:'), { target: { value: 'short1' } });
    fireEvent.click(screen.getByRole('button', { name: /s'inscrire/i }));

    expect(
      await screen.findByText('Le mot de passe doit faire au moins 8 caractères')
    ).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  describe('on success', () => {
    beforeEach(() => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('shows a success message, resets the form and redirects to /login after a delay', async () => {
      vi.mocked(axios.post).mockResolvedValue({ data: { id: 1, email: 'user@example.com' } });
      renderRegister();

      fillValidForm();
      fireEvent.click(screen.getByRole('button', { name: /s'inscrire/i }));

      expect(
        await screen.findByText('Inscription réussie ! Redirection vers la page de connexion...')
      ).toBeInTheDocument();
      expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/register'), {
        email: 'user@example.com',
        password: 'password123',
      });

      await vi.advanceTimersByTimeAsync(2000);

      expect(mockNavigate).toHaveBeenCalledWith('/login');
    });
  });

  it('shows the server error message on a validation error response', async () => {
    vi.mocked(axios.post).mockRejectedValue({
      response: { data: { error: 'Cet email est déjà utilisé' } },
    });
    renderRegister();

    fillValidForm();
    fireEvent.click(screen.getByRole('button', { name: /s'inscrire/i }));

    expect(await screen.findByText('Cet email est déjà utilisé')).toBeInTheDocument();
  });

  it('joins field-level "details" errors from the server into a single message', async () => {
    vi.mocked(axios.post).mockRejectedValue({
      response: { data: { details: { email: 'Email already taken', password: 'Too weak' } } },
    });
    renderRegister();

    fillValidForm();
    fireEvent.click(screen.getByRole('button', { name: /s'inscrire/i }));

    expect(await screen.findByText('Email already taken, Too weak')).toBeInTheDocument();
  });
});
