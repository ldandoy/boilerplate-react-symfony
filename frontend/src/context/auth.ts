import { createContext } from "react";
import type { AuthContextType } from "../types/AuthContext";

export const AuthContext = createContext<AuthContextType | undefined>(undefined);
