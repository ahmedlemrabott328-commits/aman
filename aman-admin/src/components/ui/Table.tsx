import { type ReactNode } from 'react';

interface Column<T> {
  header: string;
  render: (row: T) => ReactNode;
  className?: string;
}

interface TableProps<T> {
  columns: Column<T>[];
  rows: T[];
  keyField: (row: T) => string | number;
  loading?: boolean;
  emptyState?: ReactNode;
}

export function Table<T>({ columns, rows, keyField, loading, emptyState }: TableProps<T>) {
  if (loading) {
    return (
      <div className="flex h-40 items-center justify-center text-ink-soft">
        <span className="h-5 w-5 animate-spin rounded-full border-2 border-indigo-400 border-t-transparent" />
      </div>
    );
  }

  if (rows.length === 0) {
    return (
      <div className="flex h-40 items-center justify-center pattern-khatem rounded-lg">
        <p className="text-sm text-ink-soft">{emptyState ?? 'لا توجد بيانات لعرضها'}</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full border-collapse text-sm">
        <thead>
          <tr className="border-b border-sand-deep text-right text-xs font-semibold uppercase tracking-wide text-ink-soft">
            {columns.map((col) => (
              <th key={col.header} className="px-4 py-3">{col.header}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={keyField(row)} className="border-b border-sand-dim last:border-0 hover:bg-sand/60">
              {columns.map((col) => (
                <td key={col.header} className={col.className ?? 'px-4 py-3 text-ink'}>
                  {col.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
