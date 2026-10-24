import { useState, useEffect } from "react";
import axios from "axios";
import api, { setAccessToken } from "../lib/axios";
import { getUserFromToken } from "../lib/jwt";
import { AuthContext } from "./auth";
import type { User } from "../types/User";

function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Au montage, tente de récupérer la session via refresh token
  useEffect(() => {
    const initAuth = async () => {
      try {
        // Utilise axios directement pour éviter l'interceptor
        const response = await axios.post(
          `${import.meta.env.VITE_API_URL}/token/refresh`,
          {},
          { withCredentials: true }
        );
        const { token } = response.data;
        setAccessToken(token);
        setUser(getUserFromToken(token));
      } catch {
        // Pas de session valide, c'est normal
        setAccessToken(null);
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    initAuth();
  }, []);

  const login = async (email: string, password: string) => {
    const response = await api.post("/login_check", { email, password });
    const { token } = response.data;
    setAccessToken(token);
    setUser(getUserFromToken(token));
  };

  const logout = async () => {
    // Utilise axios directement pour éviter l'interceptor qui tente un refresh
    try {
      await axios.post(
        `${import.meta.env.VITE_API_URL}/token/invalidate`,
        {},
        { withCredentials: true }
      );
    } catch {
      // Ignore les erreurs de logout
    }
    // Toujours nettoyer le state, même si l'appel API échoue
    setAccessToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated: !!user,
        isLoading,
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export { AuthProvider };
