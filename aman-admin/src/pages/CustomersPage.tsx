import { useState } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Pagination } from '@/components/ui/Pagination';
import { Badge } from '@/components/ui/Badge';
import { Input, Select } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { api, extractErrorMessage } from '@/lib/api';
import type { Customer } from '@/types';
import { format } from 'date-fns';

export function CustomersPage() {
  usePageTitle('الزبائن');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const { data, meta, loading, page, setPage, refetch } = usePaginatedResource<Customer>('/customers', {
    search: search || undefined,
    status: status || undefined,
  });

  async function toggleBlock(customer: Customer) {
    const blocking = customer.status === 'active';
    const confirmed = window.confirm(
      blocking ? `هل تريد حظر الزبون "${customer.full_name ?? customer.phone}"؟` : `هل تريد رفع الحظر عن هذا الزبون؟`,
    );
    if (!confirmed) return;

    try {
      await api.post(`/customers/${customer.id}/block`, { blocked: blocking });
      refetch();
    } catch (err) {
      alert(extractErrorMessage(err));
    }
  }

  return (
    <div className="space-y-4">
      <Card className="flex flex-wrap items-center gap-3">
        <Input
          placeholder="بحث بالاسم أو الهاتف..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-xs"
        />
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-40">
          <option value="">كل الحالات</option>
          <option value="active">نشط</option>
          <option value="blocked">محظور</option>
        </Select>
      </Card>

      <Card className="p-0">
        <Table<Customer>
          loading={loading}
          rows={data}
          keyField={(c) => c.id}
          emptyState="لا يوجد زبائن مطابقون للبحث"
          columns={[
            { header: 'الاسم', render: (c) => c.full_name ?? '—' },
            { header: 'الهاتف', className: 'tabular px-4 py-3', render: (c) => c.phone },
            { header: 'التقييم', render: (c) => `${c.rating_avg.toFixed(1)} ⭐ (${c.rating_count})` },
            { header: 'عدد الرحلات', render: (c) => c.trips_count ?? '—' },
            {
              header: 'الحالة',
              render: (c) => <Badge tone={c.status === 'active' ? 'teal' : 'terracotta'}>{c.status === 'active' ? 'نشط' : 'محظور'}</Badge>,
            },
            { header: 'تاريخ التسجيل', render: (c) => format(new Date(c.created_at), 'dd/MM/yyyy') },
            {
              header: '',
              render: (c) => (
                <Button size="sm" variant={c.status === 'active' ? 'danger' : 'success'} onClick={() => toggleBlock(c)}>
                  {c.status === 'active' ? 'حظر' : 'رفع الحظر'}
                </Button>
              ),
            },
          ]}
        />
        <Pagination meta={meta} page={page} onChange={setPage} />
      </Card>
    </div>
  );
}
