import { type InputHTMLAttributes, forwardRef } from 'react';
import clsx from 'clsx';

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...props }, ref) => (
    <input
      ref={ref}
      className={clsx(
        'h-10 w-full rounded-lg border border-sand-deep bg-white px-3 text-sm text-ink placeholder:text-ink-soft/60',
        'focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100',
        className,
      )}
      {...props}
    />
  ),
);
Input.displayName = 'Input';

export const Select = forwardRef<HTMLSelectElement, React.SelectHTMLAttributes<HTMLSelectElement>>(
  ({ className, children, ...props }, ref) => (
    <select
      ref={ref}
      className={clsx(
        'h-10 rounded-lg border border-sand-deep bg-white px-3 text-sm text-ink',
        'focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100',
        className,
      )}
      {...props}
    >
      {children}
    </select>
  ),
);
Select.displayName = 'Select';
