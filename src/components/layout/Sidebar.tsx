import { NavLink } from 'react-router-dom';
import clsx from 'clsx';
import { useAuth } from '@/contexts/AuthContext';

const navItems = [
  { to: '/', label: 'لوحة المعلومات', icon: DashboardIcon, permission: null },
  { to: '/customers', label: 'الزبائن', icon: CustomersIcon, permission: 'customers.view' },
  { to: '/captains', label: 'الكباتن', icon: CaptainsIcon, permission: 'captains.view' },
  { to: '/trips', label: 'الرحلات', icon: TripsIcon, permission: 'trips.view' },
  { to: '/pricing', label: 'التسعير والعمولات', icon: PricingIcon, permission: 'pricing.manage' },
  { to: '/cities', label: 'المدن', icon: CitiesIcon, permission: 'cities.manage' },
  { to: '/wallets', label: 'المحافظ', icon: WalletIcon, permission: 'wallets.view' },
];

export function Sidebar() {
  const { admin, can } = useAuth();

  return (
    <aside className="flex h-screen w-64 flex-col border-l border-sand-deep/60 bg-indigo-700 text-white">
      <div className="flex h-16 items-center gap-2 px-6">
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold text-sm font-bold text-indigo-900">أ</div>
        <span className="text-lg font-extrabold tracking-tight">AMAN</span>
      </div>

      <nav className="flex-1 space-y-1 px-3 py-4">
        {navItems
          .filter((item) => !item.permission || can(item.permission))
          .map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-white/10 text-white'
                    : 'text-indigo-100/70 hover:bg-white/5 hover:text-white',
                )
              }
            >
              <item.icon className="h-5 w-5 shrink-0" />
              {item.label}
            </NavLink>
          ))}
      </nav>

      <div className="border-t border-white/10 px-4 py-4">
        <p className="truncate text-sm font-semibold text-white">{admin?.full_name}</p>
        <p className="truncate text-xs text-indigo-100/60">{admin?.roles.join('، ')}</p>
      </div>
    </aside>
  );
}

/* أيقونات SVG بسيطة (بلا مكتبة خارجية) بخط واحد متسق */
function iconProps() {
  return { fill: 'none', stroke: 'currentColor', strokeWidth: 1.75, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const, viewBox: '0 0 24 24' };
}
function DashboardIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>; }
function CustomersIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><circle cx="9" cy="8" r="3.25"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="8.5" r="2.5"/><path d="M15.5 14.2c2.8.4 5 2.8 5 5.8"/></svg>; }
function CaptainsIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><path d="M3 13l1.5-5A2 2 0 0 1 6.4 6.5h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="3" y="13" width="18" height="6" rx="1.5"/><circle cx="7.5" cy="19" r="1.25"/><circle cx="16.5" cy="19" r="1.25"/></svg>; }
function TripsIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><path d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 14V6m0 2L9 6"/></svg>; }
function PricingIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><circle cx="12" cy="12" r="9"/><path d="M9.5 15.5c.4.7 1.3 1.2 2.5 1.2 1.6 0 2.7-.8 2.7-1.9 0-2.6-5.2-1.2-5.2-3.8 0-1.1 1.1-1.9 2.7-1.9 1.2 0 2.1.5 2.5 1.2M12 7.5v9"/></svg>; }
function CitiesIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><path d="M4 21V9l5-3v15M4 21h16M14 21V4l5 3v14M9 21v-4h1M9 13v-1h1M9 9V8h1"/></svg>; }
function WalletIcon(p: { className?: string }) { return <svg {...p} {...iconProps()}><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14.5h1.5"/></svg>; }
