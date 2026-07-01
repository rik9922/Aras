/**
 * Helpers for the portal API envelope:
 * { status, message, error_type, data }
 */

export function unwrapApiPayload<T = any>(response: any): T {
  if (
    response &&
    typeof response === 'object' &&
    response.status === 'success' &&
    response.data !== undefined
  ) {
    return response.data as T;
  }

  return response as T;
}

/** Unwrap DataTables server-side responses before jQuery DataTables reads them. */
export function dataTableAjaxDataFilter(raw: string): string {
  try {
    const parsed = JSON.parse(raw);
    return JSON.stringify(unwrapApiPayload(parsed) ?? parsed);
  } catch {
    return raw;
  }
}

export function isApiSuccess(response: any): boolean {
  return response?.status === 'success';
}

export function apiErrorMessage(error: any, fallback = 'An error occurred. Please try again.'): string {
  return error?.error?.message || fallback;
}
