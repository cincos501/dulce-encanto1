import React from 'react'
import { cn } from '@/shared/utils/cn'
import { HelperText } from './HelperText'

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  error?: string;
  suffix?: React.ReactNode;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, error, suffix, ...props }, ref) => {
    return (
      <div className="space-y-1 w-full">
        <div className="relative flex items-center w-full">
          <input
            ref={ref}
            className={cn(
              'w-full px-4 py-2.5 rounded-lg bg-surface border text-text-main text-xs transition-all duration-200 disabled:bg-stone-50 disabled:text-text-sub/50 outline-none',
              error
                ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-200'
                : 'border-border focus:border-primary focus:ring-1 focus:ring-stone-200',
              suffix && 'pr-10',
              className
            )}
            {...props}
          />
          {suffix && (
            <div className="absolute right-3 flex items-center justify-center pointer-events-auto">
              {suffix}
            </div>
          )}
        </div>
        {error && (
          <HelperText error className="text-[10px] pl-1 font-semibold animate-fade-in">
            {error}
          </HelperText>
        )}
      </div>
    )
  }
)

Input.displayName = 'Input'
