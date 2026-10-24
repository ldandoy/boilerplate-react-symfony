import { describe, it, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useAuth } from './useAuth';
import { useTheme } from './useTheme';
import { useToast } from './useToast';

describe('context hooks', () => {
  it.each([
    ['useAuth', useAuth, 'useAuth must be used within an AuthProvider'],
    ['useTheme', useTheme, 'useTheme must be used within a ThemeProvider'],
    ['useToast', useToast, 'useToast must be used within a ToastProvider'],
  ])('%s throws when used outside its provider', (_name, hook, message) => {
    // React logue l'erreur levée pendant le rendu : on la masque pour garder une sortie lisible
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => renderHook(() => hook())).toThrow(message);

    consoleError.mockRestore();
  });
});
