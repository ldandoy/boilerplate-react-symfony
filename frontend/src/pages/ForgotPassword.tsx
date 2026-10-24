import { useState } from "react";
import { Link } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import axios from "axios";
import { useToast } from "../hooks/useToast";
import { forgotPasswordSchema, type ForgotPasswordFormData } from "../schemas/forgotPasswordSchema";
import { getApiErrorMessage } from "../lib/apiError";

const ForgotPassword = () => {
  const toast = useToast();
  const [serverError, setServerError] = useState<string>("");
  const [successMessage, setSuccessMessage] = useState<string>("");

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  const onSubmit = async (data: ForgotPasswordFormData) => {
    setServerError("");
    setSuccessMessage("");

    try {
      await axios.post(`${import.meta.env.VITE_API_URL}/forgot-password`, {
        email: data.email,
      });
      setSuccessMessage("Si cet email correspond à un compte, vous recevrez un lien de réinitialisation.");
      toast.success("Demande envoyée !");
    } catch (error) {
      const errorMessage = getApiErrorMessage(
        error,
        "Une erreur est survenue lors de la demande"
      );
      setServerError(errorMessage);
      toast.error(errorMessage);
    }
  };

  return (
    <div className="container mx-auto w-1/4 mt-10">
      <div>
        <h2 className="text-4xl font-bold text-center">Mot de passe oublié</h2>
        <div className="text-center text-gray-500">Saisissez votre email pour réinitialiser votre mot de passe</div>
      </div>

      <form className="mt-6 space-y-4 border border-gray-300 p-4 rounded bg-gray-50" onSubmit={handleSubmit(onSubmit)}>
        {serverError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {serverError}
          </div>
        )}

        {successMessage && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {successMessage}
          </div>
        )}

        <div className="mb-4 flex flex-col">
          <label htmlFor="email">Email:</label>
          <input
            id="email"
            type="email"
            {...register("email")}
            className="border border-gray-300 rounded p-2 dark:bg-gray-700 dark:border-gray-600"
            placeholder="Email"
          />
          {errors.email && (
            <p className="text-red-500 text-sm mt-1">{errors.email.message}</p>
          )}
        </div>

        <button
          className="bg-blue-800 hover:cursor-pointer hover:bg-blue-900 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
          type="submit"
          disabled={isSubmitting}
        >
          {isSubmitting ? "Envoi..." : "Envoyer le lien"}
        </button>

        <div className="text-center mt-4">
          <hr className="my-4 text-gray-200" />
          <p className="text-sm">
            <Link
              to="/login"
              className="text-blue-800 hover:text-blue-900 font-semibold"
            >
              Retour à la connexion
            </Link>
          </p>
        </div>
      </form>
    </div>
  );
};

export default ForgotPassword;
