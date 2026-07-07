import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
}

export const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, label, error, ...props }, ref) => {
    return (
      <div className="space-y-1">
        <div className="flex items-center gap-3">
          <input
            ref={ref}
            type="checkbox"
            className={cn(
              'w-4 h-4 text-primary border-border rounded focus:ring-stone-250/20 focus:ring-2 outline-none transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed',
              className
            )}
            {...props}
          />
          {label && (
            <label className="text-xs font-bold text-text-main cursor-pointer select-none">
              {label}
            </label>
          )}
        </div>
        {error && (
          <span className="text-[10px] text-red-500 block font-semibold pl-1 animate-fade-in">
            {error}
          </span>
        )}
      </div>
    )
  }
)

Checkbox.displayName = 'Checkbox'
