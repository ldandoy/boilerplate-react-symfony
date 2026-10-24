import { useState } from "react";
import { useNavigate, Link } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useToast } from "../hooks/useToast";
import { useAuth } from "../hooks/useAuth";
import { loginSchema, type LoginFormData } from "../schemas/loginSchema";
import { getApiErrorMessage } from "../lib/apiError";

const Login = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const { login } = useAuth();

  const [serverError, setServerError] = useState<string>("");

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = async (data: LoginFormData) => {
    setServerError("");

    try {
      await login(data.email, data.password);
      toast.success("Connexion réussie !");
      navigate("/");
    } catch (error) {
      const errorMessage = getApiErrorMessage(
        error,
        "Une erreur est survenue lors de la connexion"
      );
      setServerError(errorMessage);
      toast.error(errorMessage);
    }
  };

  return (
    <div className="container mx-auto w-1/4 mt-10">
      <div>
        <h2 className="text-4xl font-bold text-center">Connexion</h2>
        <div className="text-center text-gray-500">Accédez à votre espace</div>
      </div>


      <form className="mt-6 space-y-4 border border-gray-300 dark:border-gray-600 p-4 rounded bg-gray-50 dark:bg-gray-700" onSubmit={handleSubmit(onSubmit)}>
        {serverError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {serverError}
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

        <div className="mb-4 flex flex-col">
          <div className="flex justify-between">
            <label htmlFor="password">Mot de passe:</label>
            <div>
              <Link className="text-blue-800 hover:text-blue-900 font-semibold" to="/forgot-password">Mot de passe oublié ?</Link>
            </div>
          </div>
          <input
            id="password"
            type="password"
            {...register("password")}
            className="border border-gray-300 rounded p-2 dark:bg-gray-700 dark:border-gray-600"
            placeholder="Mot de passe"
          />
          {errors.password && (
            <p className="text-red-500 text-sm mt-1">{errors.password.message}</p>
          )}
        </div>

        <button
          className="bg-blue-800 hover:cursor-pointer hover:bg-blue-900 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
          type="submit"
          disabled={isSubmitting}
        >
          {isSubmitting ? "Connexion..." : "Se connecter"}
        </button>

        <div className="text-center mt-4">
          <hr className="my-4 text-gray-200" />
          <p className="text-sm">
            Pas encore de compte ?{" "}
            <Link
              to="/register"
              className="text-blue-800 hover:text-blue-900 font-semibold"
            >
              Créer un compte
            </Link>
          </p>
        </div>
      </form>
    </div>
  );
};

export default Login;
