import { useState, useRef, useEffect } from "react";
import { NavLink, useNavigate } from "react-router";
import { ThemeButton } from "./ThemeButton";
import { useAuth } from "../hooks/useAuth";

export const Navbar = () => {
  const { user, isAuthenticated, isLoading, logout } = useAuth();
  const navigate = useNavigate();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Fermer le dropdown si on clique en dehors
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsDropdownOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleLogout = async () => {
    await logout();
    setIsDropdownOpen(false);
    navigate("/login");
  };

  return (
    <nav className="flex flex-row justify-between p-4">
      <ul className="flex">
        <li className="mx-4">
          <NavLink to="/">Home</NavLink>
        </li>
        <li className="mx-4">
          <NavLink to="/about">About</NavLink>
        </li>
      </ul>
      <ul className="flex items-center">
        <li>
          <ThemeButton />
        </li>

        {isLoading ? (
          <li className="mx-4">
            <span className="text-gray-400">...</span>
          </li>
        ) : isAuthenticated && user ? (
          <li className="mx-4 relative" ref={dropdownRef}>
            <button
              onClick={() => setIsDropdownOpen(!isDropdownOpen)}
              className="flex items-center gap-2 hover:text-blue-500"
            >
              {user.email}
              <svg
                className={`w-4 h-4 transition-transform ${isDropdownOpen ? "rotate-180" : ""}`}
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            {isDropdownOpen && (
              <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                <div className="py-1">
                  <div className="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    {user.email}
                  </div>
                  <NavLink
                    to="/me"
                    onClick={() => setIsDropdownOpen(false)}
                    className="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Mon profil
                  </NavLink>
                  <button
                    onClick={handleLogout}
                    className="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Déconnexion
                  </button>
                </div>
              </div>
            )}
          </li>
        ) : (
          <>
            <li className="mx-4">
              <NavLink to="/login">Login</NavLink>
            </li>
            <li className="mx-4">
              <NavLink to="/register">Register</NavLink>
            </li>
          </>
        )}
      </ul>
    </nav>
  );
};
