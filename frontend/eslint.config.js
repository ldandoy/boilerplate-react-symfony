import js from '@eslint/js';
import globals from 'globals';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import tseslint from 'typescript-eslint';
import eslintConfigPrettier from 'eslint-config-prettier';
import { defineConfig, globalIgnores } from 'eslint/config';

export default defineConfig([
  globalIgnores(['dist', 'coverage']),
  {
    files: ['**/*.{ts,tsx}'],
    extends: [
      js.configs.recommended,
      tseslint.configs.recommended,
      reactHooks.configs.flat.recommended,
      reactRefresh.configs.vite,
    ],
    languageOptions: {
      ecmaVersion: 2020,
      globals: globals.browser,
    },
    rules: {
      'no-console': 'warn',
      '@typescript-eslint/no-explicit-any': 'error',
    },
  },
  {
    // Les appels HTTP passent par l'instance api de src/lib/axios.ts (Bearer + refresh JWT)
    files: ['src/**/*.{ts,tsx}'],
    ignores: ['src/**/*.test.{ts,tsx}', 'src/test/**'],
    rules: {
      'no-restricted-globals': [
        'error',
        { name: 'fetch', message: "Utiliser l'instance api de src/lib/axios.ts." },
      ],
      'no-restricted-imports': [
        'error',
        {
          paths: [
            {
              name: 'axios',
              message:
                "Utiliser l'instance api de src/lib/axios.ts. axios brut est réservé aux appels publics : ajouter le fichier à la liste blanche de eslint.config.js.",
            },
          ],
        },
      ],
    },
  },
  {
    // Appels publics (login, inscription, mot de passe…) : axios brut pour ne pas déclencher le refresh
    files: [
      'src/lib/axios.ts',
      'src/context/AuthContext.tsx',
      'src/pages/Register.tsx',
      'src/pages/ForgotPassword.tsx',
      'src/pages/ResetPassword.tsx',
      'src/pages/VerifyEmail.tsx',
    ],
    rules: {
      'no-restricted-imports': 'off',
    },
  },
  eslintConfigPrettier,
]);
