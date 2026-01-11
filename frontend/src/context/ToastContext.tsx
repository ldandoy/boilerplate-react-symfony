import { createContext } from "react";
import { toast, ToastContainer, type ToastOptions } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import type { ToastContextType } from "../types/ToastContext";

const ToastContext = createContext<ToastContextType | undefined>(undefined);

function ToastProvider({ children }: { children: React.ReactNode }) {
  const success = (message: string, options?: ToastOptions) => {
    toast.success(message, options);
  };

  const error = (message: string, options?: ToastOptions) => {
    toast.error(message, options);
  };

  const info = (message: string, options?: ToastOptions) => {
    toast.info(message, options);
  };

  const warning = (message: string, options?: ToastOptions) => {
    toast.warning(message, options);
  };

  return (
    <ToastContext.Provider value={{ success, error, info, warning }}>
      {children}
      <ToastContainer
        position="top-right"
        autoClose={5000}
        hideProgressBar={false}
        newestOnTop={false}
        closeOnClick
        rtl={false}
        pauseOnFocusLoss
        draggable
        pauseOnHover
        theme="light"
      />
    </ToastContext.Provider>
  );
}

export { ToastContext, ToastProvider };
