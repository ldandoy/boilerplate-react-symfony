import type { User } from "../types/User";

interface JwtPayload {
  exp: number;
  iat: number;
  roles: string[];
  username?: string;
  email?: string;
  id?: number;
}

export const decodeJwt = (token: string): JwtPayload | null => {
  try {
    const parts = token.split(".");
    if (parts.length !== 3) return null;

    const payload = parts[1];
    const decoded = atob(payload.replace(/-/g, "+").replace(/_/g, "/"));
    return JSON.parse(decoded);
  } catch {
    return null;
  }
};

export const getUserFromToken = (token: string): User | null => {
  const payload = decodeJwt(token);
  if (!payload) return null;

  return {
    id: payload.id || 0,
    email: payload.email || payload.username || "",
    roles: payload.roles || [],
  };
};

export const isTokenExpired = (token: string): boolean => {
  const payload = decodeJwt(token);
  if (!payload) return true;

  const now = Math.floor(Date.now() / 1000);
  return payload.exp < now;
};
