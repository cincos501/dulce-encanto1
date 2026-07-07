import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface SelectOption {
  value: string | number;
  label: string;
}

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  options: SelectOption[];
  error?: string;
  placeholder?: string;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, options, error, placeholder, ...props }, ref) => {
    return (
      <div className="space-y-1 w-full">
        <select
          ref={ref}
          className={cn(
            'w-full px-4 py-2.5 rounded-lg bg-surface border text-text-main text-xs transition-all duration-200 disabled:bg-stone-50 disabled:text-text-sub/50 outline-none appearance-none',
            error
              ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-200'
              : 'border-border focus:border-primary focus:ring-1 focus:ring-stone-200',
            className
          )}
          {...props}
        >
          {placeholder && <option value="">{placeholder}</option>}
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
        {error && (
          <span className="text-[10px] text-red-500 block font-semibold pl-1 animate-fade-in">
            {error}
          </span>
        )}
      </div>
    )
  }
)

Select.displayName = 'Select'
