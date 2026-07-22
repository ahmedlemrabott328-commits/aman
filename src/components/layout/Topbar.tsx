import { useState } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/Button';

export function Topbar({ title }: { title: string }) {
  const { logout, admin } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="flex h-16 items-center justify-between border-b border-sand-deep/60 bg-white px-6">
      <h1 className="text-xl font-extrabold text-ink">{title}</h1>

      <div className="relative">
        <button
          onClick={() => setMenuOpen((v) => !v)}
          className="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-sand-dim"
        >
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">
            {admin?.full_name?.charAt(0) ?? 'أ'}
          </span>
        </button>

        {menuOpen && (
          <div className="absolute left-0 mt-2 w-48 rounded-lg border border-sand-deep/60 bg-white py-1 shadow-card">
            <div className="border-b border-sand-dim px-3 py-2">
              <p className="truncate text-sm font-semibold text-ink">{admin?.full_name}</p>
              <p className="truncate text-xs text-ink-soft">{admin?.email}</p>
            </div>
            <Button variant="ghost" size="sm" className="mx-1 mt-1 w-[calc(100%-8px)] justify-start" onClick={logout}>
              تسجيل الخروج
            </Button>
          </div>
        )}
      </div>
    </header>
  );
}
