import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface SwitchProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  description?: string;
}

export const Switch = React.forwardRef<HTMLInputElement, SwitchProps>(
  ({ className, label, description, ...props }, ref) => {
    return (
      <div className="flex items-start justify-between gap-4 p-4 bg-background border border-border rounded-lg w-full">
        <div className="space-y-0.5">
          {label && (
            <span className="text-xs font-bold text-text-main block">{label}</span>
          )}
          {description && (
            <span className="text-[10px] text-text-sub block leading-normal">{description}</span>
          )}
        </div>
        <label className="relative inline-flex items-center cursor-pointer select-none">
          <input
            ref={ref}
            type="checkbox"
            className="sr-only peer"
            {...props}
          />
          <div className={cn(
            "w-9 h-5 bg-stone-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-stone-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary",
            className
          )}></div>
        </label>
      </div>
    )
  }
)

Switch.displayName = 'Switch'
