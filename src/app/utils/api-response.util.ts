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

export interface DataTableResolvedResponse {
  payload: any;
  rows: any[];
}

/**
 * Normalize DataTables server-side AJAX responses for both:
 * - legacy flat: { draw, recordsTotal, recordsFiltered, data: [] }
 * - API envelope: { status, message, data: { draw, recordsTotal, data: [] } }
 *
 * Also patches pagination fields back onto the root object so DataTables
 * server-side mode keeps working when only dataSrc is applied.
 */
export function resolveDataTableResponse(json: any): DataTableResolvedResponse {
  if (json?.status === 'failed') {
    return { payload: json ?? {}, rows: [] };
  }

  let payload = json;

  if (json?.status === 'success' && json?.data !== undefined) {
    payload = json.data;
  }

  const rows = Array.isArray(payload?.data) ? payload.data : [];

  if (json && payload && json !== payload && typeof payload === 'object') {
    if (payload.draw !== undefined) {
      json.draw = payload.draw;
    }
    if (payload.recordsTotal !== undefined) {
      json.recordsTotal = payload.recordsTotal;
    }
    if (payload.recordsFiltered !== undefined) {
      json.recordsFiltered = payload.recordsFiltered;
    }
  }

  return {
    payload: payload && typeof payload === 'object' && !Array.isArray(payload) ? payload : {},
    rows,
  };
}

/** Unwrap DataTables server-side responses before jQuery DataTables reads them. */
export function dataTableAjaxDataFilter(raw: string): string {
  try {
    const parsed = JSON.parse(raw);
    const { payload, rows } = resolveDataTableResponse(parsed);

    if (parsed?.status === 'success') {
      return JSON.stringify({
        ...payload,
        data: rows,
      });
    }

    return JSON.stringify(payload ?? parsed);
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
