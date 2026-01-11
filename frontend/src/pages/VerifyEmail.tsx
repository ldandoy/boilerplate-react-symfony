import { useEffect, useState } from "react";
import { useSearchParams, useNavigate, Link } from "react-router";
import axios from "axios";
import { useToast } from "../hooks/useToast";

const VerifyEmail = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const toast = useToast();
  const [status, setStatus] = useState<"loading" | "success" | "error">(
    "loading"
  );
  const [message, setMessage] = useState<string>("");

  useEffect(() => {
    const verifyEmail = async () => {
      const userId = searchParams.get("userId");
      const signedUrl = searchParams.get("signedUrl");

      if (!signedUrl || !userId) {
        setStatus("error");
        setMessage("Paramètres de vérification manquants");
        toast.error("Paramètres de vérification manquants");
        return;
      }

      try {
        const response = await axios.post(
          `${import.meta.env.VITE_API_URL}/verify-email`,
          {
            userId: parseInt(userId),
            signedUrl
          },
          {
            headers: {
              'Content-Type': 'application/json'
            }
          }
        );

        setStatus("success");
        setMessage(response.data.message || "Email vérifié avec succès !");
        toast.success("Email vérifié avec succès !");

        setTimeout(() => {
          navigate("/login");
        }, 3000);
      } catch (error: any) {
        setStatus("error");
        const errorMessage =
          error.response?.data?.error ||
          error.response?.data?.message ||
          "Une erreur est survenue lors de la vérification de l'email";
        setMessage(errorMessage);
        toast.error(errorMessage);
        console.error("Erreur lors de la vérification:", error);
      }
    };

    verifyEmail();
  }, [searchParams, navigate, toast]);

  return (
    <div className="container mx-auto w-1/4 mt-10">
      <div>
        <h2 className="text-4xl font-bold text-center">Vérification</h2>
        <div className="text-center text-gray-500">Validation de votre adresse email</div>
      </div>

      <div className="mt-6 space-y-4 border border-gray-300 p-8 rounded bg-gray-50 flex flex-col items-center">
        {status === "loading" && (
          <div className="flex flex-col items-center space-y-4">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-800"></div>
            <p className="text-gray-600 dark:text-gray-400">
              Vérification en cours...
            </p>
          </div>
        )}

        {status === "success" && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded w-full text-center">
            <p className="font-semibold">{message}</p>
            <p className="text-sm mt-2">
              Redirection vers la page de connexion...
            </p>
          </div>
        )}

        {status === "error" && (
          <div className="space-y-6 w-full text-center">
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              <p className="font-semibold">{message}</p>
            </div>
            <button
              onClick={() => navigate("/register")}
              className="bg-blue-800 hover:cursor-pointer hover:bg-blue-900 text-white w-full py-2 px-4 rounded"
            >
              Retour à l'inscription
            </button>
          </div>
        )}

        {(status === "success" || status === "loading") && (
          <div className="w-full text-center mt-4">
            <hr className="my-4 text-gray-200" />
            <Link to="/login" className="text-blue-800 hover:text-blue-900 font-semibold text-sm">
              Aller à la connexion
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default VerifyEmail;