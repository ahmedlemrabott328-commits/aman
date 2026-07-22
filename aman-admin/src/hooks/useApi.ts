import { useCallback, useEffect, useState } from 'react';
import { api, extractErrorMessage } from '@/lib/api';
import type { ApiEnvelope, PaginatedData } from '@/types';

/**
 * جلب مورد مُرقَّم صفحيًا (Customers, Captains, Trips...) مع دعم الفلاتر.
 * إعادة الجلب تتم تلقائيًا عند تغيّر endpoint أو الفلاتر (JSON.stringify كمقارنة بسيطة وكافية هنا).
 */
export function usePaginatedResource<T>(endpoint: string, filters: Record<string, unknown> = {}) {
  const [data, setData] = useState<T[]>([]);
  const [meta, setMeta] = useState<PaginatedData<T>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  const filtersKey = JSON.stringify(filters);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get<ApiEnvelope<PaginatedData<T>>>(endpoint, {
        params: { ...filters, page },
      });
      setData(response.data.data.data);
      setMeta(response.data.data.meta);
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint, filtersKey, page]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // إعادة الصفحة إلى 1 عند تغيّر الفلاتر (سلوك متوقع لأي جدول)
  useEffect(() => {
    setPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filtersKey]);

  return { data, meta, loading, error, page, setPage, refetch: fetchData };
}

/** جلب مورد واحد غير مرقَّم (Dashboard stats مثلاً) */
export function useResource<T>(endpoint: string, deps: unknown[] = []) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get<ApiEnvelope<T>>(endpoint);
      setData(response.data.data);
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint, ...deps]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return { data, loading, error, refetch: fetchData };
}
