import { useState } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Pagination } from '@/components/ui/Pagination';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Modal } from '@/components/ui/Modal';
import { api, extractErrorMessage } from '@/lib/api';
import type { Wallet } from '@/types';

function formatMoney(value: number, currency: string) {
  return `${new Intl.NumberFormat('ar').format(value)} ${currency}`;
}

export function WalletsPage() {
  usePageTitle('المحافظ');
  const [search, setSearch] = useState('');
  const { data, meta, loading, page, setPage, refetch } = usePaginatedResource<Wallet>('/wallets', {
    search: search || undefined,
  });

  const [adjustTarget, setAdjustTarget] = useState<Wallet | null>(null);
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submitAdjustment() {
    if (!adjustTarget || !amount || !description) return;
    setSaving(true);
    setError(null);
    try {
      await api.post(`/wallets/${adjustTarget.captain.id}/adjust`, { amount: Number(amount), description });
      setAdjustTarget(null);
      setAmount('');
      setDescription('');
      refetch();
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-4">
      <Card className="flex flex-wrap items-center gap-3">
        <Input placeholder="بحث باسم الكابتن أو هاتفه..." value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-xs" />
      </Card>

      <Card className="p-0">
        <Table<Wallet>
          loading={loading}
          rows={data}
          keyField={(w) => w.id}
          emptyState="لا توجد محافظ مطابقة"
          columns={[
            { header: 'الكابتن', render: (w) => w.captain.full_name },
            { header: 'الهاتف', className: 'tabular px-4 py-3', render: (w) => w.captain.phone },
            {
              header: 'الرصيد',
              className: 'tabular px-4 py-3 font-bold',
              render: (w) => (
                <span className={w.balance < 0 ? 'text-terracotta' : 'text-teal'}>
                  {formatMoney(w.balance, w.currency)}
                </span>
              ),
            },
            {
              header: '',
              render: (w) => <Button size="sm" variant="secondary" onClick={() => setAdjustTarget(w)}>تعديل الرصيد</Button>,
            },
          ]}
        />
        <Pagination meta={meta} page={page} onChange={setPage} />
      </Card>

      <Modal
        open={!!adjustTarget}
        onClose={() => { setAdjustTarget(null); setError(null); }}
        title={`تعديل رصيد: ${adjustTarget?.captain.full_name ?? ''}`}
        footer={
          <>
            <Button variant="secondary" onClick={() => setAdjustTarget(null)}>إلغاء</Button>
            <Button loading={saving} onClick={submitAdjustment}>تأكيد التعديل</Button>
          </>
        }
      >
        <div className="space-y-3">
          <p className="text-sm text-ink-soft">
            الرصيد الحالي: <span className="tabular font-semibold text-ink">{adjustTarget ? formatMoney(adjustTarget.balance, adjustTarget.currency) : '—'}</span>
          </p>
          <div>
            <label className="mb-1.5 block text-sm font-semibold text-ink">المبلغ (موجب = إضافة، سالب = خصم)</label>
            <Input type="number" step="0.01" className="tabular" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="مثال: 500 أو -200" />
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-semibold text-ink">سبب التعديل</label>
            <Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="مثال: مكافأة أداء شهر يوليو" />
          </div>
          {error && <p className="rounded-lg bg-terracotta-light px-3 py-2 text-sm text-terracotta">{error}</p>}
          <p className="text-xs text-ink-soft">يُسجَّل كل تعديل في سجل العمليات (Audit Log) مع اسم المسؤول ووقت التنفيذ.</p>
        </div>
      </Modal>
    </div>
  );
}
