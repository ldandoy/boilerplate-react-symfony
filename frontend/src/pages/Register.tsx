import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { registerSchema, type RegisterFormData } from '../schemas/signupSchema';
import axios from 'axios';
import { useState } from 'react';
import { useNavigate } from 'react-router';

const Register = () => {
  const navigate = useNavigate();
  const [serverError, setServerError] = useState<string>('');
  const [successMessage, setSuccessMessage] = useState<string>('');

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = async (data: RegisterFormData) => {
    setServerError('');
    setSuccessMessage('');

    try {
      await axios.post(
        `${import.meta.env.VITE_API_URL}/register`,
        {
          email: data.email,
          password: data.password,
        }
      );

      setSuccessMessage('Inscription réussie ! Redirection vers la page de connexion...');
      reset();

      setTimeout(() => {
        navigate('/login');
      }, 2000);
    } catch (error: any) {
      if (error.response?.data?.error) {
        setServerError(error.response.data.error);
      } else if (error.response?.data?.details) {
        const details = error.response.data.details;
        setServerError(Object.values(details).join(', '));
      } else {
        setServerError('Une erreur est survenue lors de l\'inscription');
      }
      console.error('Erreur lors de l\'inscription:', error);
    }
  };

  return (
    <div className="w-1/4 mx-auto mt-10">
      <h1 className="text-2xl font-bold mb-6 text-center">Inscription</h1>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {serverError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {serverError}
          </div>
        )}

        {successMessage && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {successMessage}
          </div>
        )}

        <div>
          <label htmlFor="email" className="block mb-2 font-semibold">
            Email
          </label>
          <input
            id="email"
            type="email"
            {...register('email')}
            className="border border-gray-300 rounded p-2 w-full dark:bg-gray-700 dark:border-gray-600"
            placeholder="votre@email.com"
          />
          {errors.email && (
            <p className="text-red-500 text-sm mt-1">{errors.email.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="password" className="block mb-2 font-semibold">
            Mot de passe
          </label>
          <input
            id="password"
            type="password"
            {...register('password')}
            className="border border-gray-300 rounded p-2 w-full dark:bg-gray-700 dark:border-gray-600"
            placeholder="••••••"
          />
          {errors.password && (
            <p className="text-red-500 text-sm mt-1">{errors.password.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="confirmPassword" className="block mb-2 font-semibold">
            Confirmer le mot de passe
          </label>
          <input
            id="confirmPassword"
            type="password"
            {...register('confirmPassword')}
            className="border border-gray-300 rounded p-2 w-full dark:bg-gray-700 dark:border-gray-600"
            placeholder="••••••"
          />
          {errors.confirmPassword && (
            <p className="text-red-500 text-sm mt-1">{errors.confirmPassword.message}</p>
          )}
        </div>

        <button
          type="submit"
          disabled={isSubmitting}
          className="bg-blue-500 hover:bg-blue-700 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
        >
          {isSubmitting ? 'Inscription en cours...' : "S'inscrire"}
        </button>

        <div className="text-center mt-4">
          <p className="text-sm">
            Déjà compagnon ?{' '}
            <a href="/login" className="text-blue-500 hover:text-blue-700 font-semibold">
              Se connecter
            </a>
          </p>
        </div>
      </form>
    </div>
  );
};

export default Register;