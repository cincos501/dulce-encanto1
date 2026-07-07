import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface RadioOption {
  value: string | number;
  label: string;
}

export interface RadioGroupProps extends React.InputHTMLAttributes<HTMLInputElement> {
  options: RadioOption[];
  selectedValue?: string | number;
  onChangeValue?: (value: string | number) => void;
  error?: string;
}

export const RadioGroup = React.forwardRef<HTMLInputElement, RadioGroupProps>(
  ({ className, options, selectedValue, onChangeValue, error, name, ...props }, ref) => {
    return (
      <div className="space-y-2">
        <div className="flex flex-col gap-2">
          {options.map((option) => (
            <label 
              key={option.value} 
              className="inline-flex items-center gap-3 cursor-pointer select-none"
            >
              <input
                ref={ref}
                type="radio"
                name={name}
                value={option.value}
                checked={selectedValue === option.value}
                onChange={() => onChangeValue?.(option.value)}
                className={cn(
                  'w-4.5 h-4.5 text-rose-500 border-stone-300 focus:ring-rose-450 focus:ring-2 focus:ring-rose-100 outline-none transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed',
                  className
                )}
                {...props}
              />
              <span className="text-sm font-semibold text-stone-700">{option.label}</span>
            </label>
          ))}
        </div>
        {error && (
          <span className="text-xs text-rose-500 block font-medium pl-1 animate-fade-in">
            {error}
          </span>
        )}
      </div>
    )
  }
)

RadioGroup.displayName = 'RadioGroup'
