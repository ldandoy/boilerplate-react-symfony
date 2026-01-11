import * as z from 'zod';

export const forgotPasswordSchema = z.object({
  email: z.string().email("Veuillez saisir une adresse email valide"),
});

export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;
