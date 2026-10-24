import { describe, it, expect } from 'vitest';
import { forgotPasswordSchema } from './forgotPasswordSchema';

describe('forgotPasswordSchema', () => {
  it('accepts a valid email', () => {
    expect(forgotPasswordSchema.safeParse({ email: 'user@example.com' }).success).toBe(true);
  });

  it('rejects an invalid email', () => {
    const result = forgotPasswordSchema.safeParse({ email: 'not-an-email' });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues[0].message).toBe('Veuillez saisir une adresse email valide');
    }
  });
});
