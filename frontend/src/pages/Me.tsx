import { Navigate } from "react-router";
import { useAuth } from "../hooks/useAuth";

const Me = () => {
  const { user, isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="w-1/2 mx-auto mt-10 text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p className="mt-4 text-gray-600 dark:text-gray-400">Chargement...</p>
      </div>
    );
  }

  if (!isAuthenticated || !user) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="w-1/2 mx-auto mt-10">
      <h1 className="text-2xl font-bold mb-6 text-center">Mon profil</h1>

      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-4">
        <div>
          <label className="block text-sm font-semibold text-gray-500 dark:text-gray-400">
            Email
          </label>
          <p className="text-lg">{user.email}</p>
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-500 dark:text-gray-400">
            Rôles
          </label>
          <div className="flex flex-wrap gap-2 mt-1">
            {user.roles.map((role) => (
              <span
                key={role}
                className="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm"
              >
                {role}
              </span>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Me;
