import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Important: envoie les cookies HTTP-only
});

let accessToken: string | null = null;

export const setAccessToken = (token: string | null) => {
  accessToken = token;
};

export const getAccessToken = () => accessToken;

// Interceptor pour ajouter le token aux requêtes
api.interceptors.request.use(
  (config) => {
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor pour gérer le refresh automatique
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Si erreur 401 et pas déjà une tentative de retry
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        // Appel refresh (le cookie est envoyé automatiquement grâce à withCredentials)
        const response = await axios.post(
          `${import.meta.env.VITE_API_URL}/token/refresh`,
          {},
          { withCredentials: true }
        );

        const { token } = response.data;
        setAccessToken(token);

        // Retry la requête originale avec le nouveau token
        originalRequest.headers.Authorization = `Bearer ${token}`;
        return api(originalRequest);
      } catch (refreshError) {
        // Refresh échoué, on rejette simplement l'erreur
        // Le AuthContext gère la déconnexion
        setAccessToken(null);
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
