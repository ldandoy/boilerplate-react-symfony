import { useState } from "react";
import { useNavigate, Link } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useToast } from "../hooks/useToast";
import { useAuth } from "../hooks/useAuth";
import { loginSchema, type LoginFormData } from "../schemas/loginSchema";

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
    } catch (error: any) {
      const errorMessage =
        error.response?.data?.error ||
        error.response?.data?.message ||
        "Une erreur est survenue lors de la connexion";
      setServerError(errorMessage);
      toast.error(errorMessage);
      console.error("Erreur lors de la connexion:", error);
    }
  };

  return (
    <div className="container mx-auto w-1/4 mt-10">
      <div>
        <div>Les Compagnons du Dev</div>
        <h2>Connexion</h2>
        <div>Accédez à votre espace développeur</div>
      </div>


      <form onSubmit={handleSubmit(onSubmit)}>
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
          <div>
            <label htmlFor="password">Mot de passe:</label>
            <div>
              <Link to="">Mot de passe oublié ?</Link>
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
          className="bg-blue-500 hover:bg-blue-700 text-white w-full py-2 px-4 rounded disabled:bg-blue-300 disabled:cursor-not-allowed"
          type="submit"
          disabled={isSubmitting}
        >
          {isSubmitting ? "Connexion..." : "Se connecter"}
        </button>

        <div className="text-center mt-4">
          <p className="text-sm">
            Nouveau compagnon ?{" "}
            <a
              href="/register"
              className="text-blue-500 hover:text-blue-700 font-semibold"
            >
              Créer un compte
            </a>
          </p>
        </div>
      </form>
    </div>
  );
};

export default Login;
