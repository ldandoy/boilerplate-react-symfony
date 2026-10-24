import { createContext } from "react";
import type { ThemeContextType } from "../types/ThemeContext";

export const ThemeContext = createContext<ThemeContextType | undefined>(undefined);
