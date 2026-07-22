import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1/admin',
  headers: { Accept: 'application/json' },
});

// إرفاق توكن Sanctum تلقائيًا بكل طلب
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('aman_admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// عند انتهاء صلاحية الجلسة (401) نُعيد التوجيه لصفحة الدخول تلقائيًا
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('aman_admin_token');
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  },
);

/** استخراج رسالة خطأ مقروءة من استجابة الـ API بصيغتنا الموحدة { success, message, errors } */
export function extractErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? 'حدث خطأ غير متوقع، حاول مجددًا';
  }
  return 'حدث خطأ غير متوقع، حاول مجددًا';
}
