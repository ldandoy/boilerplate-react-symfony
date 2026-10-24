import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import axios from 'axios';
import VerifyEmail from './VerifyEmail';

const mockNavigate = vi.fn();
// Référence stable : le useEffect de la page dépend de `toast`
const mockToast = { success: vi.fn(), error: vi.fn() };

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
  useToast: () => mockToast,
}));

const signedUrl = 'http://localhost/api/verify-email?expires=1&signature=abc&token=xyz';

function renderVerifyEmail(url = `/verify-email?userId=42&signedUrl=${encodeURIComponent(signedUrl)}`) {
  return render(
    <MemoryRouter initialEntries={[url]}>
      <VerifyEmail />
    </MemoryRouter>
  );
}

describe('VerifyEmail page', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    mockToast.success.mockReset();
    mockToast.error.mockReset();
    vi.mocked(axios.post).mockReset();
  });

  it('shows an error without calling the API when parameters are missing', async () => {
    renderVerifyEmail('/verify-email');

    expect(await screen.findByText('Paramètres de vérification manquants')).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('shows the server error and lets the user go back to registration', async () => {
    vi.mocked(axios.post).mockRejectedValue({
      response: { data: { detail: 'The link to verify your email is invalid.' } },
    });
    renderVerifyEmail();

    expect(await screen.findByText('The link to verify your email is invalid.')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: "Retour à l'inscription" }));
    expect(mockNavigate).toHaveBeenCalledWith('/register');
  });

  describe('on success', () => {
    beforeEach(() => {
      vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('verifies the email with the URL parameters and redirects to /login', async () => {
      vi.mocked(axios.post).mockResolvedValue({ data: { message: 'Email verified successfully.' } });
      renderVerifyEmail();

      expect(screen.getByText('Vérification en cours...')).toBeInTheDocument();
      expect(await screen.findByText('Email verified successfully.')).toBeInTheDocument();
      expect(axios.post).toHaveBeenCalledTimes(1);
      expect(axios.post).toHaveBeenCalledWith(
        expect.stringContaining('/verify-email'),
        { userId: 42, signedUrl },
        expect.anything()
      );
      expect(mockToast.success).toHaveBeenCalledWith('Email vérifié avec succès !');

      await vi.advanceTimersByTimeAsync(3000);

      expect(mockNavigate).toHaveBeenCalledWith('/login');
    });
  });
});
