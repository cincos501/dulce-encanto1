import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface HelperTextProps extends React.HTMLAttributes<HTMLSpanElement> {
  error?: boolean;
}

export const HelperText: React.FC<HelperTextProps> = ({ className, error, children, ...props }) => {
  return (
    <span
      className={cn(
        'text-xs block pl-1 font-medium select-none',
        error ? 'text-rose-500' : 'text-stone-400',
        className
      )}
      {...props}
    >
      {children}
    </span>
  )
}
