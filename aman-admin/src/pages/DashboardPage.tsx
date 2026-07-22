import { useResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import type { DashboardStats } from '@/types';
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid,
} from 'recharts';

const APPROVAL_LABELS: Record<string, string> = {
  pending: 'بانتظار المراجعة', approved: 'معتمد', rejected: 'مرفوض', suspended: 'موقوف',
};

const SERVICE_LABELS: Record<string, string> = {
  ride: 'نقل ركاب', airport: 'المطار', delivery: 'التوصيل',
};

function formatMoney(value: number, currency: string) {
  return `${new Intl.NumberFormat('ar').format(value)} ${currency}`;
}

export function DashboardPage() {
  usePageTitle('لوحة المعلومات');
  const { data, loading } = useResource<DashboardStats>('/dashboard');

  if (loading || !data) {
    return <div className="flex h-64 items-center justify-center text-ink-soft">جارٍ التحميل...</div>;
  }

  const dailyTrips = Object.entries(data.trips_by_day).map(([day, total]) => ({ day, total }));

  return (
    <div className="space-y-6">
      {/* بطاقات المؤشرات الرئيسية */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="إجمالي الزبائن" value={data.customers_total} tone="indigo" />
        <StatCard label="إجمالي الكباتن" value={data.captains_total} sub={`${data.captains_online_now} متصل الآن`} tone="teal" />
        <StatCard label="رحلات هذا الشهر" value={data.trips_total} sub={`${data.trips_completed} مكتملة`} tone="gold" />
        <StatCard
          label="إيرادات العمولة"
          value={formatMoney(data.revenue.commission, 'MRU')}
          sub={`إجمالي الرحلات: ${formatMoney(data.revenue.gross, 'MRU')}`}
          tone="terracotta"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* الرحلات اليومية */}
        <Card className="lg:col-span-2">
          <h3 className="mb-4 text-sm font-bold text-ink">الرحلات خلال الفترة</h3>
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={dailyTrips}>
              <CartesianGrid strokeDasharray="3 3" stroke="#ECE4D3" />
              <XAxis dataKey="day" tick={{ fontSize: 11, fill: '#5B6178' }} />
              <YAxis tick={{ fontSize: 11, fill: '#5B6178' }} allowDecimals={false} />
              <Tooltip
                contentStyle={{ borderRadius: 10, border: '1px solid #ECE4D3', fontSize: 13 }}
                labelStyle={{ fontWeight: 700 }}
              />
              <Bar dataKey="total" name="عدد الرحلات" fill="#2E3A6E" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </Card>

        {/* توزيع الكباتن حسب الحالة */}
        <Card>
          <h3 className="mb-4 text-sm font-bold text-ink">حالة اعتماد الكباتن</h3>
          <div className="space-y-3">
            {Object.entries(APPROVAL_LABELS).map(([key, label]) => {
              const count = data.captains_by_status[key as keyof typeof data.captains_by_status] ?? 0;
              const max = Math.max(...Object.values(data.captains_by_status), 1);
              return (
                <div key={key}>
                  <div className="mb-1 flex justify-between text-xs">
                    <span className="text-ink-soft">{label}</span>
                    <span className="tabular font-semibold text-ink">{count}</span>
                  </div>
                  <div className="h-2 rounded-full bg-sand-dim">
                    <div
                      className="h-2 rounded-full bg-indigo-400"
                      style={{ width: `${(count / max) * 100}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>

          <h3 className="mb-3 mt-6 text-sm font-bold text-ink">الرحلات حسب الخدمة</h3>
          <div className="flex gap-4">
            {Object.entries(data.trips_by_service).map(([code, total]) => (
              <div key={code} className="flex-1 rounded-lg bg-sand px-3 py-2 text-center">
                <p className="tabular text-lg font-extrabold text-indigo-500">{total}</p>
                <p className="text-xs text-ink-soft">{SERVICE_LABELS[code] ?? code}</p>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}

function StatCard({
  label, value, sub, tone,
}: { label: string; value: string | number; sub?: string; tone: 'indigo' | 'teal' | 'gold' | 'terracotta' }) {
  const toneBar: Record<string, string> = {
    indigo: 'bg-indigo-500', teal: 'bg-teal', gold: 'bg-gold', terracotta: 'bg-terracotta',
  };
  return (
    <Card className="relative overflow-hidden">
      <span className={`absolute inset-y-0 right-0 w-1 ${toneBar[tone]}`} />
      <p className="text-xs font-semibold text-ink-soft">{label}</p>
      <p className="tabular mt-2 text-2xl font-extrabold text-ink">{value}</p>
      {sub && <p className="mt-1 text-xs text-ink-soft">{sub}</p>}
    </Card>
  );
}
