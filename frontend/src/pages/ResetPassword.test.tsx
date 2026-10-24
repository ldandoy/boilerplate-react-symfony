import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import axios from 'axios';
import ResetPassword from './ResetPassword';

const mockNavigate = vi.fn();
const mockToastSuccess = vi.fn();
const mockToastError = vi.fn();

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

vi.mock('../hooks/useToast', () => ({
  useToast: () => ({ success: mockToastSuccess, error: mockToastError }),
}));

function renderResetPassword(url = '/reset-password?token=abc123') {
  return render(
    <MemoryRouter initialEntries={[url]}>
      <ResetPassword />
    </MemoryRouter>
  );
}

function fillPasswords(password: string, confirmation = password) {
  fireEvent.change(screen.getByLabelText('Nouveau mot de passe:'), { target: { value: password } });
  fireEvent.change(screen.getByLabelText('Confirmer le mot de passe:'), { target: { value: confirmation } });
  fireEvent.click(screen.getByRole('button', { name: /réinitialiser le mot de passe/i }));
}

describe('ResetPassword page', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    mockToastSuccess.mockReset();
    mockToastError.mockReset();
    vi.mocked(axios.post).mockReset();
  });

  it('shows an error when the passwords do not match', async () => {
    renderResetPassword();

    fillPasswords('password123', 'different123');

    expect(await screen.findByText('Les mots de passe ne correspondent pas')).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('shows an error when the token is missing from the URL', async () => {
    renderResetPassword('/reset-password');

    fillPasswords('password123');

    expect(await screen.findByText('Token invalide ou manquant')).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('shows the server error message when the token is rejected', async () => {
    vi.mocked(axios.post).mockRejectedValue({
      response: { data: { detail: 'The reset password link is invalid.' } },
    });
    renderResetPassword();

    fillPasswords('password123');

    expect(await screen.findByText('The reset password link is invalid.')).toBeInTheDocument();
    expect(mockToastError).toHaveBeenCalledWith('The reset password link is invalid.');
  });

  describe('on success', () => {
    beforeEach(() => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('sends the token and new password, then redirects to /login after a delay', async () => {
      vi.mocked(axios.post).mockResolvedValue({ data: { message: 'Password has been reset successfully.' } });
      renderResetPassword();

      fillPasswords('password123');

      expect(await screen.findByText('Password has been reset successfully.')).toBeInTheDocument();
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('/reset-password'),
        { token: 'abc123', password: 'password123' },
        expect.anything()
      );
      expect(mockNavigate).not.toHaveBeenCalled();

      await vi.advanceTimersByTimeAsync(3000);

      expect(mockNavigate).toHaveBeenCalledWith('/login');
    });
  });
});
