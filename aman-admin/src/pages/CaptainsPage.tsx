import { useState } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Pagination } from '@/components/ui/Pagination';
import { Badge } from '@/components/ui/Badge';
import { Input, Select } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Modal } from '@/components/ui/Modal';
import { api, extractErrorMessage } from '@/lib/api';
import type { ApiEnvelope, Captain, ApprovalStatus } from '@/types';

const STATUS_LABELS: Record<ApprovalStatus, string> = {
  pending: 'بانتظار المراجعة', approved: 'معتمد', rejected: 'مرفوض', suspended: 'موقوف',
};
const STATUS_TONE: Record<ApprovalStatus, 'gold' | 'teal' | 'terracotta' | 'neutral'> = {
  pending: 'gold', approved: 'teal', rejected: 'terracotta', suspended: 'neutral',
};

export function CaptainsPage() {
  usePageTitle('الكباتن');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const { data, meta, loading, page, setPage, refetch } = usePaginatedResource<Captain>('/captains', {
    search: search || undefined,
    approval_status: status || undefined,
  });

  const [selected, setSelected] = useState<Captain | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [rejectMode, setRejectMode] = useState<'reject' | 'suspend' | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  async function openDetail(captainId: number) {
    setDetailLoading(true);
    setSelected(null);
    try {
      const res = await api.get<ApiEnvelope<Captain>>(`/captains/${captainId}`);
      setSelected(res.data.data);
    } catch (err) {
      alert(extractErrorMessage(err));
    } finally {
      setDetailLoading(false);
    }
  }

  async function approve(captain: Captain) {
    setActionLoading(true);
    try {
      await api.post(`/captains/${captain.id}/approve`);
      setSelected(null);
      refetch();
    } catch (err) {
      alert(extractErrorMessage(err));
    } finally {
      setActionLoading(false);
    }
  }

  async function submitReject(captain: Captain) {
    if (!rejectReason.trim()) return;
    setActionLoading(true);
    try {
      const endpoint = rejectMode === 'suspend' ? 'suspend' : 'reject';
      await api.post(`/captains/${captain.id}/${endpoint}`, { reason: rejectReason });
      setSelected(null);
      setRejectMode(null);
      setRejectReason('');
      refetch();
    } catch (err) {
      alert(extractErrorMessage(err));
    } finally {
      setActionLoading(false);
    }
  }

  async function reviewDocument(captainId: number, documentId: number, decision: 'approved' | 'rejected') {
    let rejection_reason: string | undefined;
    if (decision === 'rejected') {
      const reason = window.prompt('سبب رفض الوثيقة:');
      if (!reason) return;
      rejection_reason = reason;
    }
    try {
      await api.post(`/captains/${captainId}/documents/${documentId}/review`, { status: decision, rejection_reason });
      openDetail(captainId); // إعادة تحميل تفاصيل الكابتن لعرض حالة الوثيقة المحدَّثة
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
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-48">
          <option value="">كل الحالات</option>
          {Object.entries(STATUS_LABELS).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </Select>
      </Card>

      <Card className="p-0">
        <Table<Captain>
          loading={loading}
          rows={data}
          keyField={(c) => c.id}
          emptyState="لا يوجد كباتن مطابقون للبحث"
          columns={[
            { header: 'الاسم', render: (c) => c.full_name },
            { header: 'الهاتف', className: 'tabular px-4 py-3', render: (c) => c.phone },
            { header: 'المدينة', render: (c) => c.city?.name ?? '—' },
            { header: 'التقييم', render: (c) => `${c.rating_avg.toFixed(1)} ⭐` },
            {
              header: 'الاتصال',
              render: (c) => <Badge tone={c.is_online ? 'teal' : 'neutral'}>{c.is_online ? 'متصل' : 'غير متصل'}</Badge>,
            },
            {
              header: 'الحالة',
              render: (c) => <Badge tone={STATUS_TONE[c.approval_status]}>{STATUS_LABELS[c.approval_status]}</Badge>,
            },
            {
              header: '',
              render: (c) => <Button size="sm" variant="secondary" onClick={() => openDetail(c.id)}>عرض التفاصيل</Button>,
            },
          ]}
        />
        <Pagination meta={meta} page={page} onChange={setPage} />
      </Card>

      {/* لوحة تفاصيل الكابتن */}
      <Modal
        open={detailLoading || !!selected}
        onClose={() => { setSelected(null); setRejectMode(null); setRejectReason(''); }}
        title={selected ? selected.full_name : 'جارٍ التحميل...'}
      >
        {detailLoading && <p className="text-sm text-ink-soft">جارٍ التحميل...</p>}

        {selected && (
          <div className="max-h-[70vh] space-y-5 overflow-y-auto">
            <div className="grid grid-cols-2 gap-3 text-sm">
              <Info label="الهاتف" value={selected.phone} mono />
              <Info label="البطاقة الوطنية" value={selected.national_id ?? '—'} mono />
              <Info label="المدينة" value={selected.city?.name ?? '—'} />
              <Info label="الحالة" value={<Badge tone={STATUS_TONE[selected.approval_status]}>{STATUS_LABELS[selected.approval_status]}</Badge>} />
            </div>

            {selected.rejection_reason && (
              <p className="rounded-lg bg-terracotta-light px-3 py-2 text-sm text-terracotta">
                سبب آخر رفض/إيقاف: {selected.rejection_reason}
              </p>
            )}

            <div>
              <h3 className="mb-2 text-sm font-bold text-ink">الوثائق</h3>
              <div className="space-y-2">
                {selected.documents?.length ? selected.documents.map((doc) => (
                  <div key={doc.id} className="flex items-center justify-between rounded-lg border border-sand-deep/60 px-3 py-2">
                    <div>
                      <p className="text-sm font-medium text-ink">{doc.document_type}</p>
                      <a href={doc.file_url} target="_blank" rel="noreferrer" className="text-xs text-indigo-500 hover:underline">
                        عرض الملف
                      </a>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge tone={doc.status === 'approved' ? 'teal' : doc.status === 'rejected' ? 'terracotta' : 'gold'}>
                        {doc.status === 'approved' ? 'معتمدة' : doc.status === 'rejected' ? 'مرفوضة' : 'قيد المراجعة'}
                      </Badge>
                      {doc.status === 'pending' && (
                        <>
                          <Button size="sm" variant="success" onClick={() => reviewDocument(selected.id, doc.id, 'approved')}>قبول</Button>
                          <Button size="sm" variant="danger" onClick={() => reviewDocument(selected.id, doc.id, 'rejected')}>رفض</Button>
                        </>
                      )}
                    </div>
                  </div>
                )) : <p className="text-sm text-ink-soft">لا توجد وثائق مرفوعة بعد</p>}
              </div>
            </div>

            {rejectMode ? (
              <div className="space-y-2 rounded-lg border border-terracotta/30 bg-terracotta-light/40 p-3">
                <label className="text-sm font-semibold text-ink">
                  {rejectMode === 'suspend' ? 'سبب الإيقاف' : 'سبب الرفض'}
                </label>
                <Input value={rejectReason} onChange={(e) => setRejectReason(e.target.value)} placeholder="اكتب السبب..." />
                <div className="flex justify-end gap-2 pt-1">
                  <Button size="sm" variant="secondary" onClick={() => setRejectMode(null)}>إلغاء</Button>
                  <Button size="sm" variant="danger" loading={actionLoading} onClick={() => submitReject(selected)}>
                    تأكيد {rejectMode === 'suspend' ? 'الإيقاف' : 'الرفض'}
                  </Button>
                </div>
              </div>
            ) : (
              <div className="flex justify-end gap-2 border-t border-sand-dim pt-4">
                {selected.approval_status === 'pending' && (
                  <>
                    <Button variant="danger" onClick={() => setRejectMode('reject')}>رفض الطلب</Button>
                    <Button variant="success" loading={actionLoading} onClick={() => approve(selected)}>اعتماد الكابتن</Button>
                  </>
                )}
                {selected.approval_status === 'approved' && (
                  <Button variant="danger" onClick={() => setRejectMode('suspend')}>إيقاف الحساب</Button>
                )}
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}

function Info({ label, value, mono }: { label: string; value: React.ReactNode; mono?: boolean }) {
  return (
    <div>
      <p className="text-xs text-ink-soft">{label}</p>
      <p className={mono ? 'tabular text-sm font-medium text-ink' : 'text-sm font-medium text-ink'}>{value}</p>
    </div>
  );
}
