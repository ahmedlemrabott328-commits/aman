import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, extractErrorMessage } from '@/lib/api';
import type { AdminProfile, ApiEnvelope } from '@/types';

interface AuthContextValue {
  admin: AdminProfile | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  can: (permission: string) => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

const TOKEN_KEY = 'aman_admin_token';
const ADMIN_KEY = 'aman_admin_profile';

export function AuthProvider({ children }: { children: ReactNode }) {
  const [admin, setAdmin] = useState<AdminProfile | null>(() => {
    const cached = localStorage.getItem(ADMIN_KEY);
    return cached ? JSON.parse(cached) : null;
  });
  const [loading, setLoading] = useState(false);

  async function login(email: string, password: string) {
    setLoading(true);
    try {
      const response = await api.post<ApiEnvelope<{ token: string; admin: AdminProfile }>>(
        '/auth/login',
        { email, password },
      );
      const { token, admin: profile } = response.data.data;
      localStorage.setItem(TOKEN_KEY, token);
      localStorage.setItem(ADMIN_KEY, JSON.stringify(profile));
      setAdmin(profile);
    } catch (err) {
      throw new Error(extractErrorMessage(err));
    } finally {
      setLoading(false);
    }
  }

  function logout() {
    api.post('/auth/logout').catch(() => undefined); // إبطال التوكن من طرف الخادم؛ لا نمنع تسجيل الخروج المحلي إن فشل
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(ADMIN_KEY);
    setAdmin(null);
  }

  function can(permission: string): boolean {
    return admin?.permissions.includes(permission) ?? false;
  }

  // مزامنة الجلسة عبر تبويبات المتصفح المتعددة
  useEffect(() => {
    function onStorage(e: StorageEvent) {
      if (e.key === TOKEN_KEY && !e.newValue) {
        setAdmin(null);
      }
    }
    window.addEventListener('storage', onStorage);
    return () => window.removeEventListener('storage', onStorage);
  }, []);

  return (
    <AuthContext.Provider value={{ admin, loading, login, logout, can }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
