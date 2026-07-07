import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  error?: string;
}

export const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, error, ...props }, ref) => {
    return (
      <div className="space-y-1 w-full">
        <textarea
          ref={ref}
          className={cn(
            'w-full px-4 py-2.5 rounded-lg bg-surface border text-text-main text-xs transition-all duration-200 resize-none disabled:bg-stone-50 disabled:text-text-sub/50 outline-none',
            error
              ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-200'
              : 'border-border focus:border-primary focus:ring-1 focus:ring-stone-200',
            className
          )}
          {...props}
        />
        {error && (
          <span className="text-[10px] text-red-500 block font-semibold pl-1 animate-fade-in">
            {error}
          </span>
        )}
      </div>
    )
  }
)

Textarea.displayName = 'Textarea'
