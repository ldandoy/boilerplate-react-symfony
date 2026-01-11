import * as z from 'zod';

export const resetPasswordSchema = z.object({
  token: z.string().min(1, "Le token est requis"),
  password: z.string().min(8, "Le mot de passe doit faire au moins 8 caractères"),
  password_cfg: z.string(),
}).refine((data) => data.password === data.password_cfg, {
  message: "Les mots de passe ne correspondent pas",
  path: ["password_cfg"],
});

export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

// Type pour l'API (sans password_cfg)
export type ResetPasswordApiData = Omit<ResetPasswordFormData, 'password_cfg'>;
