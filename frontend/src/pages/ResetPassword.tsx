import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useSearchParams, useNavigate } from 'react-router';
import axios from 'axios';
import { useToast } from '../hooks/useToast';
import { resetPasswordSchema, type ResetPasswordFormData, type ResetPasswordApiData } from '../schemas/resetPasswordSchema';

const ResetPassword = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const toast = useToast();
  const token = searchParams.get('token') || '';

  const [serverError, setServerError] = useState<string>('');
  const [successMessage, setSuccessMessage] = useState<string>('');

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      token,
    },
  });

  const onSubmit = async (data: ResetPasswordFormData) => {
    setServerError('');
    setSuccessMessage('');

    const apiData: ResetPasswordApiData = {
      token: data.token,
      password: data.password,
    };

    try {
      const response = await axios.post(
        `${import.meta.env.VITE_API_URL}/reset-password`,
        apiData,
        {
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );

      setSuccessMessage(response.data.message || 'Mot de passe réinitialisé avec succès !');
      toast.success('Mot de passe réinitialisé avec succès !');
      reset();

      setTimeout(() => {
        navigate('/login');
      }, 3000);
    } catch (error: any) {
      const errorMessage =
        error.response?.data?.error ||
        error.response?.data?.message ||
        'Une erreur est survenue lors de la réinitialisation du mot de passe';
      setServerError(errorMessage);
      toast.error(errorMessage);
      console.error('Erreur lors de la réinitialisation:', error);
    }
  };

  return (
    <div className="w-1/4 mx-auto mt-10">
      <h1 className="text-2xl font-bold mb-6 text-center">Réinitialiser le mot de passe</h1>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {serverError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {serverError}
          </div>
        )}

        {successMessage && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {successMessage}
            <p className="text-sm mt-2">Redirection vers la page de connexion...</p>
          </div>
        )}

        <input type="hidden" {...register('token')} />

        <div>
          <label htmlFor="password" className="block mb-2 font-semibold">
            Nouveau mot de passe
          </label>
          <input
            id="password"
            type="password"
            {...register('password')}
            className="border border-gray-300 rounded p-2 w-full dark:bg-gray-700 dark:border-gray-600"
            placeholder="••••••••"
          />
          {errors.password && (
            <p className="text-red-500 text-sm mt-1">{errors.password.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="password_cfg" className="block mb-2 font-semibold">
            Confirmer le mot de passe
          </label>
          <input
            id="password_cfg"
            type="password"
            {...register('password_cfg')}
            className="border border-gray-300 rounded p-2 w-full dark:bg-gray-700 dark:border-gray-600"
            placeholder="••••••••"
          />
          {errors.password_cfg && (
            <p className="text-red-500 text-sm mt-1">{errors.password_cfg.message}</p>
          )}
        </div>

        {errors.token && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            Token invalide ou manquant
          </div>
        )}

        <button
          type="submit"
          disabled={isSubmitting}
          className="bg-blue-500 hover:bg-blue-700 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
        >
          {isSubmitting ? 'Réinitialisation...' : 'Réinitialiser le mot de passe'}
        </button>

        <div className="text-center mt-4">
          <p className="text-sm">
            <a href="/login" className="text-blue-500 hover:text-blue-700 font-semibold">
              Retour à la connexion
            </a>
          </p>
        </div>
      </form>
    </div>
  );
};

export default ResetPassword;
