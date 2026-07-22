import { useState, type FormEvent } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

export function LoginPage() {
  const { admin, login, loading } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);

  if (admin) return <Navigate to="/" replace />;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    try {
      await login(email, password);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'فشل تسجيل الدخول');
    }
  }

  return (
    <div className="pattern-khatem flex min-h-screen items-center justify-center bg-sand px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 flex flex-col items-center">
          <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500 text-2xl font-bold text-gold-light">
            أ
          </div>
          <h1 className="text-2xl font-extrabold text-ink">AMAN</h1>
          <p className="mt-1 text-sm text-ink-soft">لوحة إدارة منصة النقل الذكية</p>
        </div>

        <form onSubmit={handleSubmit} className="rounded-xl border border-sand-deep/60 bg-white p-6 shadow-card">
          <div className="mb-4">
            <label htmlFor="email" className="mb-1.5 block text-sm font-semibold text-ink">
              البريد الإلكتروني
            </label>
            <Input
              id="email"
              type="email"
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@aman.mr"
              required
            />
          </div>

          <div className="mb-5">
            <label htmlFor="password" className="mb-1.5 block text-sm font-semibold text-ink">
              كلمة المرور
            </label>
            <Input
              id="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
            />
          </div>

          {error && (
            <p role="alert" className="mb-4 rounded-lg bg-terracotta-light px-3 py-2 text-sm text-terracotta">
              {error}
            </p>
          )}

          <Button type="submit" className="w-full" loading={loading}>
            تسجيل الدخول
          </Button>
        </form>
      </div>
    </div>
  );
}
