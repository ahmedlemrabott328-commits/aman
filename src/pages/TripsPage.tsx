import { useState } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Pagination } from '@/components/ui/Pagination';
import { Badge } from '@/components/ui/Badge';
import { Select } from '@/components/ui/Input';
import type { Trip, TripStatus } from '@/types';
import { format } from 'date-fns';

const STATUS_LABELS: Record<TripStatus, string> = {
  requested: 'مطلوبة', searching: 'جارٍ البحث', accepted: 'تم القبول', arrived: 'وصل الكابتن',
  in_progress: 'قيد التنفيذ', completed: 'مكتملة', cancelled: 'ملغاة', no_captain_found: 'لا يوجد كابتن',
};

const STATUS_TONE: Record<TripStatus, 'gold' | 'teal' | 'terracotta' | 'indigo' | 'neutral'> = {
  requested: 'neutral', searching: 'gold', accepted: 'indigo', arrived: 'indigo',
  in_progress: 'indigo', completed: 'teal', cancelled: 'terracotta', no_captain_found: 'terracotta',
};

export function TripsPage() {
  usePageTitle('الرحلات');
  const [status, setStatus] = useState('');
  const { data, meta, loading, page, setPage } = usePaginatedResource<Trip>('/trips', {
    status: status || undefined,
  });

  return (
    <div className="space-y-4">
      <Card className="flex flex-wrap items-center gap-3">
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-48">
          <option value="">كل الحالات</option>
          {Object.entries(STATUS_LABELS).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </Select>
      </Card>

      <Card className="p-0">
        <Table<Trip>
          loading={loading}
          rows={data}
          keyField={(t) => t.id}
          emptyState="لا توجد رحلات مطابقة"
          columns={[
            { header: 'الكود', className: 'tabular px-4 py-3 font-medium', render: (t) => t.trip_code },
            { header: 'الزبون', render: (t) => t.customer?.full_name ?? t.customer?.phone ?? '—' },
            { header: 'الكابتن', render: (t) => t.captain?.full_name ?? '—' },
            {
              header: 'الحالة',
              render: (t) => <Badge tone={STATUS_TONE[t.status]}>{STATUS_LABELS[t.status]}</Badge>,
            },
            {
              header: 'السعر',
              className: 'tabular px-4 py-3',
              render: (t) => (t.final_price ?? t.estimated_price) != null
                ? `${t.final_price ?? t.estimated_price} ${t.currency}`
                : '—',
            },
            { header: 'التاريخ', render: (t) => format(new Date(t.requested_at), 'dd/MM/yyyy HH:mm') },
          ]}
        />
        <Pagination meta={meta} page={page} onChange={setPage} />
      </Card>
    </div>
  );
}
