import clsx from 'clsx';

type Tone = 'neutral' | 'gold' | 'teal' | 'terracotta' | 'indigo';

const toneClasses: Record<Tone, string> = {
  neutral: 'bg-sand-dim text-ink-soft',
  gold: 'bg-gold/15 text-gold-dark',
  teal: 'bg-teal-light text-teal',
  terracotta: 'bg-terracotta-light text-terracotta',
  indigo: 'bg-indigo-50 text-indigo-500',
};

export function Badge({ tone = 'neutral', children }: { tone?: Tone; children: React.ReactNode }) {
  return (
    <span className={clsx('inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', toneClasses[tone])}>
      {children}
    </span>
  );
}
