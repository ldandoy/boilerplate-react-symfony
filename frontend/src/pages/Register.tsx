import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { registerSchema, type RegisterFormData } from '../schemas/signupSchema';
import axios from 'axios';
import { useState } from 'react';
import { useNavigate, Link } from 'react-router';

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
    <div className="container mx-auto w-1/4 mt-10">
      <div>
        <h2 className="text-4xl font-bold text-center">Inscription</h2>
        <div className="text-center text-gray-500">Rejoignez l'aventure</div>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="mt-6 space-y-4 border border-gray-300 dark:border-gray-600 p-4 rounded bg-gray-50 dark:bg-gray-700">
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

        <div className="mb-4 flex flex-col">
          <label htmlFor="email">Email:</label>
          <input
            id="email"
            type="email"
            {...register('email')}
            className="border border-gray-300 rounded p-2 dark:bg-gray-700 dark:border-gray-600"
            placeholder="Email"
          />
          {errors.email && (
            <p className="text-red-500 text-sm mt-1">{errors.email.message}</p>
          )}
        </div>

        <div className="mb-4 flex flex-col">
          <label htmlFor="password">Mot de passe:</label>
          <input
            id="password"
            type="password"
            {...register('password')}
            className="border border-gray-300 rounded p-2 dark:bg-gray-700 dark:border-gray-600"
            placeholder="Mot de passe"
          />
          {errors.password && (
            <p className="text-red-500 text-sm mt-1">{errors.password.message}</p>
          )}
        </div>

        <div className="mb-4 flex flex-col">
          <label htmlFor="confirmPassword">Confirmer le mot de passe:</label>
          <input
            id="confirmPassword"
            type="password"
            {...register('confirmPassword')}
            className="border border-gray-300 rounded p-2 dark:bg-gray-700 dark:border-gray-600"
            placeholder="Confirmer le mot de passe"
          />
          {errors.confirmPassword && (
            <p className="text-red-500 text-sm mt-1">{errors.confirmPassword.message}</p>
          )}
        </div>

        <button
          className="bg-blue-800 hover:cursor-pointer hover:bg-blue-900 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
          type="submit"
          disabled={isSubmitting}
        >
          {isSubmitting ? 'Inscription...' : "S'inscrire"}
        </button>

        <div className="text-center mt-4">
          <hr className="my-4 text-gray-200" />
          <p className="text-sm">
            Déjà un compte ?{' '}
            <Link
              to="/login"
              className="text-blue-800 hover:text-blue-900 font-semibold"
            >
              Se connecter
            </Link>
          </p>
        </div>
      </form>
    </div>
  );
};

export default Register;