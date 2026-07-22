import { useState, useEffect, type FormEvent } from 'react';
import { usePaginatedResource } from '@/hooks/useApi';
import { usePageTitle } from '@/components/layout/AppLayout';
import { Card } from '@/components/ui/Card';
import { Table } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import { Input, Select } from '@/components/ui/Input';
import { Modal } from '@/components/ui/Modal';
import { Badge } from '@/components/ui/Badge';
import { api, extractErrorMessage } from '@/lib/api';
import type { City, CommissionRule, PricingRule } from '@/types';

// ملاحظة: لا يوجد حاليًا endpoint لعرض قائمة الخدمات من الـ API (راجع aman-backend)،
// وترتيب المعرّفات هنا يطابق ترتيب ServicesAndCitiesSeeder. يُفضَّل إضافة GET /services لاحقًا
// بدل هذا الثبوت اليدوي إن تغيّر ترتيب الإدخال في قاعدة البيانات.
const SERVICES = [
  { id: 1, label: 'نقل الركاب' },
  { id: 2, label: 'خدمة المطار' },
  { id: 3, label: 'التوصيل' },
];

export function PricingPage() {
  usePageTitle('التسعير والعمولات');
  const [tab, setTab] = useState<'pricing' | 'commission'>('pricing');

  return (
    <div className="space-y-4">
      <div className="flex gap-2 border-b border-sand-deep/60">
        <TabButton active={tab === 'pricing'} onClick={() => setTab('pricing')}>قواعد التسعير</TabButton>
        <TabButton active={tab === 'commission'} onClick={() => setTab('commission')}>قواعد العمولة</TabButton>
      </div>

      {tab === 'pricing' ? <PricingRulesTable /> : <CommissionRulesTable />}
    </div>
  );
}

function TabButton({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
  return (
    <button
      onClick={onClick}
      className={`border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors ${
        active ? 'border-indigo-500 text-indigo-500' : 'border-transparent text-ink-soft hover:text-ink'
      }`}
    >
      {children}
    </button>
  );
}

function serviceLabel(id: number) {
  return SERVICES.find((s) => s.id === id)?.label ?? `#${id}`;
}

/* ============================= قواعد التسعير ============================= */

function PricingRulesTable() {
  const { data, loading, refetch } = usePaginatedResource<PricingRule>('/pricing-rules');
  const { data: cityList } = usePaginatedResource<City>('/cities', { per_page: 100 });
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<PricingRule | null>(null);

  function openCreate() { setEditing(null); setModalOpen(true); }
  function openEdit(rule: PricingRule) { setEditing(rule); setModalOpen(true); }

  return (
    <Card className="p-0">
      <div className="flex items-center justify-between p-4">
        <p className="text-sm text-ink-soft">الأسعار قابلة للتعديل الفوري وتنعكس على كل الرحلات الجديدة فور الحفظ</p>
        <Button size="sm" onClick={openCreate}>+ قاعدة تسعير جديدة</Button>
      </div>

      <Table<PricingRule>
        loading={loading}
        rows={data}
        keyField={(r) => r.id}
        emptyState="لا توجد قواعد تسعير بعد"
        columns={[
          { header: 'الخدمة', render: (r) => serviceLabel(r.service_id) },
          { header: 'المدينة', render: (r) => cityList.find((c) => c.id === r.city_id)?.name_ar ?? r.city_id },
          { header: 'سعر الانطلاق', className: 'tabular px-4 py-3', render: (r) => `${r.base_fare} ${r.currency}` },
          { header: 'سعر الكم', className: 'tabular px-4 py-3', render: (r) => `${r.price_per_km} ${r.currency}` },
          { header: 'سعر الدقيقة', className: 'tabular px-4 py-3', render: (r) => `${r.price_per_minute} ${r.currency}` },
          { header: 'الحد الأدنى', className: 'tabular px-4 py-3', render: (r) => `${r.min_fare} ${r.currency}` },
          { header: 'الحالة', render: (r) => <Badge tone={r.is_active ? 'teal' : 'neutral'}>{r.is_active ? 'فعّالة' : 'معطّلة'}</Badge> },
          { header: '', render: (r) => <Button size="sm" variant="secondary" onClick={() => openEdit(r)}>تعديل</Button> },
        ]}
      />

      <PricingRuleModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        rule={editing}
        cities={cityList}
        onSaved={() => { setModalOpen(false); refetch(); }}
      />
    </Card>
  );
}

function PricingRuleModal({
  open, onClose, rule, cities, onSaved,
}: { open: boolean; onClose: () => void; rule: PricingRule | null; cities: City[]; onSaved: () => void }) {
  const [form, setForm] = useState<Partial<PricingRule>>(rule ?? {
    service_id: 1, city_id: cities[0]?.id, base_fare: 0, price_per_km: 0, price_per_minute: 0, min_fare: 0, cancellation_fee: 0, is_active: true,
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // إعادة تهيئة النموذج عند فتح المودال لتعديل قاعدة مختلفة، أو عند تحميل قائمة المدن
  useEffect(() => {
    setForm(rule ?? {
      service_id: 1, city_id: cities[0]?.id, base_fare: 0, price_per_km: 0,
      price_per_minute: 0, min_fare: 0, cancellation_fee: 0, is_active: true,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [rule, open]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      if (rule) {
        await api.put(`/pricing-rules/${rule.id}`, form);
      } else {
        await api.post('/pricing-rules', form);
      }
      onSaved();
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={rule ? 'تعديل قاعدة التسعير' : 'قاعدة تسعير جديدة'}>
      <form onSubmit={handleSubmit} className="space-y-3">
        <div className="grid grid-cols-2 gap-3">
          <Field label="الخدمة">
            <Select value={form.service_id} onChange={(e) => setForm({ ...form, service_id: Number(e.target.value) })}>
              {SERVICES.map((s) => <option key={s.id} value={s.id}>{s.label}</option>)}
            </Select>
          </Field>
          <Field label="المدينة">
            <Select value={form.city_id} onChange={(e) => setForm({ ...form, city_id: Number(e.target.value) })}>
              {cities.map((c) => <option key={c.id} value={c.id}>{c.name_ar}</option>)}
            </Select>
          </Field>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <NumberField label="سعر الانطلاق (MRU)" value={form.base_fare} onChange={(v) => setForm({ ...form, base_fare: v })} />
          <NumberField label="سعر الكيلومتر (MRU)" value={form.price_per_km} onChange={(v) => setForm({ ...form, price_per_km: v })} />
          <NumberField label="سعر الدقيقة (MRU)" value={form.price_per_minute} onChange={(v) => setForm({ ...form, price_per_minute: v })} />
          <NumberField label="الحد الأدنى (MRU)" value={form.min_fare} onChange={(v) => setForm({ ...form, min_fare: v })} />
          <NumberField label="رسوم الإلغاء (MRU)" value={form.cancellation_fee} onChange={(v) => setForm({ ...form, cancellation_fee: v })} />
        </div>

        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" checked={form.is_active ?? true} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
          قاعدة فعّالة
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

/* ============================= قواعد العمولة ============================= */

function CommissionRulesTable() {
  const { data, loading, refetch } = usePaginatedResource<CommissionRule>('/commission-rules');
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<CommissionRule | null>(null);

  return (
    <Card className="p-0">
      <div className="flex items-center justify-between p-4">
        <p className="text-sm text-ink-soft">العمولة المستحقة على الكابتن تُخصم تلقائيًا من محفظته عند إنهاء كل رحلة</p>
        <Button size="sm" onClick={() => { setEditing(null); setModalOpen(true); }}>+ قاعدة عمولة جديدة</Button>
      </div>

      <Table<CommissionRule>
        loading={loading}
        rows={data}
        keyField={(r) => r.id}
        emptyState="لا توجد قواعد عمولة بعد"
        columns={[
          { header: 'الخدمة', render: (r) => serviceLabel(r.service_id) },
          { header: 'النوع', render: (r) => r.commission_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' },
          { header: 'القيمة', className: 'tabular px-4 py-3', render: (r) => r.commission_type === 'percentage' ? `${r.value}%` : `${r.value} MRU` },
          { header: 'الحالة', render: (r) => <Badge tone={r.is_active ? 'teal' : 'neutral'}>{r.is_active ? 'فعّالة' : 'معطّلة'}</Badge> },
          { header: '', render: (r) => <Button size="sm" variant="secondary" onClick={() => { setEditing(r); setModalOpen(true); }}>تعديل</Button> },
        ]}
      />

      <CommissionRuleModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        rule={editing}
        onSaved={() => { setModalOpen(false); refetch(); }}
      />
    </Card>
  );
}

function CommissionRuleModal({
  open, onClose, rule, onSaved,
}: { open: boolean; onClose: () => void; rule: CommissionRule | null; onSaved: () => void }) {
  const [form, setForm] = useState<Partial<CommissionRule>>(rule ?? { service_id: 1, commission_type: 'percentage', value: 15, is_active: true });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setForm(rule ?? { service_id: 1, commission_type: 'percentage', value: 15, is_active: true });
  }, [rule, open]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      if (rule) await api.put(`/commission-rules/${rule.id}`, form);
      else await api.post('/commission-rules', form);
      onSaved();
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={rule ? 'تعديل قاعدة العمولة' : 'قاعدة عمولة جديدة'}>
      <form onSubmit={handleSubmit} className="space-y-3">
        <Field label="الخدمة">
          <Select value={form.service_id} onChange={(e) => setForm({ ...form, service_id: Number(e.target.value) })}>
            {SERVICES.map((s) => <option key={s.id} value={s.id}>{s.label}</option>)}
          </Select>
        </Field>
        <Field label="نوع العمولة">
          <Select value={form.commission_type} onChange={(e) => setForm({ ...form, commission_type: e.target.value as 'percentage' | 'fixed' })}>
            <option value="percentage">نسبة مئوية</option>
            <option value="fixed">مبلغ ثابت</option>
          </Select>
        </Field>
        <NumberField label={form.commission_type === 'percentage' ? 'النسبة (%)' : 'المبلغ (MRU)'} value={form.value} onChange={(v) => setForm({ ...form, value: v })} />

        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" checked={form.is_active ?? true} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
          قاعدة فعّالة
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

function NumberField({ label, value, onChange }: { label: string; value: number | undefined; onChange: (v: number) => void }) {
  return (
    <Field label={label}>
      <Input type="number" step="0.01" min="0" value={value ?? 0} onChange={(e) => onChange(Number(e.target.value))} className="tabular" />
    </Field>
  );
}
