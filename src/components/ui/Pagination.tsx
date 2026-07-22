import { Button } from './Button';
import type { PaginatedData } from '@/types';

export function Pagination({
  meta, page, onChange,
}: { meta: PaginatedData<unknown>['meta'] | null; page: number; onChange: (p: number) => void }) {
  if (!meta || meta.last_page <= 1) return null;

  return (
    <div className="flex items-center justify-between border-t border-sand-dim px-4 py-3 text-sm text-ink-soft">
      <span>
        عرض {meta.total === 0 ? 0 : (page - 1) * meta.per_page + 1}–{Math.min(page * meta.per_page, meta.total)} من {meta.total}
      </span>
      <div className="flex gap-2">
        <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => onChange(page - 1)}>السابق</Button>
        <Button variant="secondary" size="sm" disabled={page >= meta.last_page} onClick={() => onChange(page + 1)}>التالي</Button>
      </div>
    </div>
  );
}
