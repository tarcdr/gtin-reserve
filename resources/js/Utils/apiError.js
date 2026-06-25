export const getFirstErrorMessage = (payload, fallback = '') => {
  const fallbackMessage = typeof fallback === 'string' ? fallback.trim() : '';

  if (typeof payload === 'string') {
    return payload.trim() || fallbackMessage;
  }

  if (!payload || typeof payload !== 'object') {
    return fallbackMessage;
  }

  const validationErrors = payload.errors;
  if (validationErrors && typeof validationErrors === 'object') {
    for (const value of Object.values(validationErrors)) {
      if (Array.isArray(value) && typeof value[0] === 'string' && value[0].trim() !== '') {
        return value[0].trim();
      }
    }
  }

  const directMessage = [payload.message, payload.error]
    .find((value) => typeof value === 'string' && value.trim() !== '');

  if (directMessage) {
    return directMessage.trim();
  }

  return fallbackMessage;
};

export const getResponseErrorMessage = async (response, fallback = '') => {
  if (!response || typeof response.json !== 'function') {
    return typeof fallback === 'string' ? fallback.trim() : '';
  }

  try {
    const payload = await response.json();
    return getFirstErrorMessage(payload, fallback);
  } catch {
    return typeof fallback === 'string' ? fallback.trim() : '';
  }
};

export const getAxiosErrorMessage = (error, fallback = '') => {
  const responseData = error?.response?.data;
  const responseErrors = responseData?.errors;

  if (error?.response?.status === 422 && responseErrors && typeof responseErrors === 'object') {
    for (const value of Object.values(responseErrors)) {
      if (Array.isArray(value) && typeof value[0] === 'string' && value[0].trim() !== '') {
        return value[0].trim();
      }
    }
  }

  return getFirstErrorMessage(responseData, error?.message || fallback);
};
