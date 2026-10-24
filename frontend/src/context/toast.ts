import { createContext } from "react";
import type { ToastContextType } from "../types/ToastContext";

export const ToastContext = createContext<ToastContextType | undefined>(undefined);
