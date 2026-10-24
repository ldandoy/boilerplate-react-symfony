import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import axios from 'axios';
import ForgotPassword from './ForgotPassword';

const mockToastSuccess = vi.fn();
const mockToastError = vi.fn();

vi.mock('axios', () => ({
  default: { post: vi.fn() },
}));

vi.mock('../hooks/useToast', () => ({
  useToast: () => ({ success: mockToastSuccess, error: mockToastError }),
}));

function renderForgotPassword() {
  return render(
    <MemoryRouter>
      <ForgotPassword />
    </MemoryRouter>
  );
}

function submit(email: string) {
  fireEvent.change(screen.getByLabelText('Email:'), { target: { value: email } });
  fireEvent.click(screen.getByRole('button', { name: /envoyer le lien/i }));
}

describe('ForgotPassword page', () => {
  beforeEach(() => {
    vi.mocked(axios.post).mockReset();
    mockToastSuccess.mockReset();
    mockToastError.mockReset();
  });

  it('shows a validation error and does not call the API on empty email', async () => {
    renderForgotPassword();

    submit('');

    expect(await screen.findByText('Veuillez saisir une adresse email valide')).toBeInTheDocument();
    expect(axios.post).not.toHaveBeenCalled();
  });

  it('sends the request and shows a neutral confirmation message', async () => {
    vi.mocked(axios.post).mockResolvedValue({ data: {} });
    renderForgotPassword();

    submit('user@example.com');

    expect(
      await screen.findByText('Si cet email correspond à un compte, vous recevrez un lien de réinitialisation.')
    ).toBeInTheDocument();
    expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/forgot-password'), {
      email: 'user@example.com',
    });
    expect(mockToastSuccess).toHaveBeenCalledWith('Demande envoyée !');
  });

  it('shows the server error message on failure', async () => {
    vi.mocked(axios.post).mockRejectedValue({ response: { data: { detail: 'Service indisponible' } } });
    renderForgotPassword();

    submit('user@example.com');

    expect(await screen.findByText('Service indisponible')).toBeInTheDocument();
    await waitFor(() => expect(mockToastError).toHaveBeenCalledWith('Service indisponible'));
  });
});
