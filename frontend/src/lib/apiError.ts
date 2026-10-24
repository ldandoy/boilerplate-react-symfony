type ApiErrorData = {
  error?: string;
  message?: string;
  // Format problem+json d'API Platform (erreurs de validation, 4xx/5xx)
  detail?: string;
  details?: Record<string, string>;
};

export const getApiErrorData = (error: unknown): ApiErrorData | undefined =>
  (error as { response?: { data?: ApiErrorData } } | null)?.response?.data;

export const getApiErrorMessage = (error: unknown, fallback: string): string => {
  const data = getApiErrorData(error);

  return data?.error || data?.message || data?.detail || fallback;
};
