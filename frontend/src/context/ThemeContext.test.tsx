import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { ThemeProvider } from './ThemeContext';
import { ThemeButton } from '../components/ThemeButton';
import { useTheme } from '../hooks/useTheme';

function ThemeValue() {
  const { theme } = useTheme();
  return <span data-testid="theme">{theme}</span>;
}

function renderTheme() {
  return render(
    <ThemeProvider>
      <ThemeValue />
      <ThemeButton />
    </ThemeProvider>
  );
}

describe('ThemeProvider', () => {
  beforeEach(() => {
    localStorage.clear();
    document.documentElement.classList.remove('dark');
  });

  it('defaults to the light theme', () => {
    renderTheme();

    expect(screen.getByTestId('theme')).toHaveTextContent('light');
    expect(document.documentElement).not.toHaveClass('dark');
    expect(localStorage.getItem('theme')).toBe('light');
  });

  it('restores the theme saved in localStorage', () => {
    localStorage.setItem('theme', 'dark');
    renderTheme();

    expect(screen.getByTestId('theme')).toHaveTextContent('dark');
    expect(document.documentElement).toHaveClass('dark');
  });

  it('ignores an invalid saved value', () => {
    localStorage.setItem('theme', 'purple');
    renderTheme();

    expect(screen.getByTestId('theme')).toHaveTextContent('light');
  });

  it('toggles between light and dark and persists the choice', () => {
    renderTheme();

    fireEvent.click(screen.getByRole('button'));
    expect(screen.getByTestId('theme')).toHaveTextContent('dark');
    expect(document.documentElement).toHaveClass('dark');
    expect(localStorage.getItem('theme')).toBe('dark');

    fireEvent.click(screen.getByRole('button'));
    expect(screen.getByTestId('theme')).toHaveTextContent('light');
    expect(document.documentElement).not.toHaveClass('dark');
  });
});
