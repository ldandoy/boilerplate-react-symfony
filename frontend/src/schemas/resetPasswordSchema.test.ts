import { describe, it, expect } from 'vitest';
import { resetPasswordSchema } from './resetPasswordSchema';

const valid = { token: 'abc123', password: 'password123', password_cfg: 'password123' };

describe('resetPasswordSchema', () => {
  it('accepts a token and two matching passwords', () => {
    expect(resetPasswordSchema.safeParse(valid).success).toBe(true);
  });

  it('rejects a missing token', () => {
    const result = resetPasswordSchema.safeParse({ ...valid, token: '' });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues[0].message).toBe('Le token est requis');
    }
  });

  it('rejects a password shorter than 8 characters', () => {
    const result = resetPasswordSchema.safeParse({ ...valid, password: 'short', password_cfg: 'short' });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues[0].message).toBe('Le mot de passe doit faire au moins 8 caractères');
    }
  });

  it('rejects mismatched passwords on the confirmation field', () => {
    const result = resetPasswordSchema.safeParse({ ...valid, password_cfg: 'different123' });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues[0].path).toEqual(['password_cfg']);
      expect(result.error.issues[0].message).toBe('Les mots de passe ne correspondent pas');
    }
  });
});
