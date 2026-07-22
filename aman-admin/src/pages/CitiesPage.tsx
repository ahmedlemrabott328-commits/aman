import { useState, useEffect, type FormEvent } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Modal } from '@/components/ui/Modal';
import { Badge } from '@/components/ui/Badge';
import { api, extractErrorMessage } from '@/lib/api';
import type { City } from '@/types';

export function CitiesPage() {
  usePageTitle('المدن');
  const { data, loading, refetch } = usePaginatedResource<City>('/cities', { per_page: 100 });
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<City | null>(null);

  return (
    <div className="space-y-4">
      <Card className="p-0">
        <div className="flex items-center justify-between p-4">
          <p className="text-sm text-ink-soft">المدن المُفعَّلة فقط تظهر للزبون عند اختيار موقع الانطلاق</p>
          <Button size="sm" onClick={() => { setEditing(null); setModalOpen(true); }}>+ مدينة جديدة</Button>
        </div>

        <Table<City>
          loading={loading}
          rows={data}
          keyField={(c) => c.id}
          emptyState="لا توجد مدن مضافة بعد"
          columns={[
            { header: 'بالعربية', render: (c) => c.name_ar },
            { header: 'بالفرنسية', render: (c) => c.name_fr },
            { header: 'بالإنجليزية', render: (c) => c.name_en },
            { header: 'الحالة', render: (c) => <Badge tone={c.is_active ? 'teal' : 'neutral'}>{c.is_active ? 'مفعّلة' : 'معطّلة'}</Badge> },
            { header: '', render: (c) => <Button size="sm" variant="secondary" onClick={() => { setEditing(c); setModalOpen(true); }}>تعديل</Button> },
          ]}
        />
      </Card>

      <CityModal open={modalOpen} onClose={() => setModalOpen(false)} city={editing} onSaved={() => { setModalOpen(false); refetch(); }} />
    </div>
  );
}

function CityModal({
  open, onClose, city, onSaved,
}: { open: boolean; onClose: () => void; city: City | null; onSaved: () => void }) {
  const [form, setForm] = useState<Partial<City>>(city ?? { name_ar: '', name_fr: '', name_en: '', is_active: true });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setForm(city ?? { name_ar: '', name_fr: '', name_en: '', is_active: true });
  }, [city, open]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      if (city) await api.put(`/cities/${city.id}`, form);
      else await api.post('/cities', form);
      onSaved();
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={city ? 'تعديل المدينة' : 'مدينة جديدة'}>
      <form onSubmit={handleSubmit} className="space-y-3">
        <Field label="الاسم بالعربية">
          <Input value={form.name_ar ?? ''} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} required />
        </Field>
        <Field label="الاسم بالفرنسية">
          <Input value={form.name_fr ?? ''} onChange={(e) => setForm({ ...form, name_fr: e.target.value })} required />
        </Field>
        <Field label="الاسم بالإنجليزية">
          <Input value={form.name_en ?? ''} onChange={(e) => setForm({ ...form, name_en: e.target.value })} required />
        </Field>
        <div className="grid grid-cols-2 gap-3">
          <Field label="خط العرض (اختياري)">
            <Input type="number" step="0.0001" className="tabular" value={form.center_lat ?? ''} onChange={(e) => setForm({ ...form, center_lat: e.target.value ? Number(e.target.value) : null })} />
          </Field>
          <Field label="خط الطول (اختياري)">
            <Input type="number" step="0.0001" className="tabular" value={form.center_lng ?? ''} onChange={(e) => setForm({ ...form, center_lng: e.target.value ? Number(e.target.value) : null })} />
          </Field>
        </div>
        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" checked={form.is_active ?? true} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
          مدينة مفعّلة
        </label>

        {error && <p className="rounded-lg bg-terracotta-light px-3 py-2 text-sm text-terracotta">{error}</p>}

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="secondary" onClick={onClose}>إلغاء</Button>
          <Button type="submit" loading={saving}>حفظ</Button>
        </div>
      </form>
    </Modal>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="mb-1.5 block text-sm font-semibold text-ink">{label}</label>
      {children}
    </div>
  );
}
