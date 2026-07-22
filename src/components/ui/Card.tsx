import clsx from 'clsx';

export function Card({ className, children }: { className?: string; children: React.ReactNode }) {
  return (
    <div className={clsx('rounded-xl border border-sand-deep/60 bg-white p-5 shadow-card', className)}>
      {children}
    </div>
  );
}
