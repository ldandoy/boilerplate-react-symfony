import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import axios from 'axios';
import App from '../App';

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

describe('App', () => {
  it('renders the layout and offers login to a visitor without session', async () => {
    vi.mocked(axios.post).mockRejectedValue(new Error('no session'));

    render(<App />);

    expect(await screen.findByRole('link', { name: 'Login' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Home' })).toBeInTheDocument();
    expect(screen.getByText(/My Website/)).toBeInTheDocument();
  });
});
