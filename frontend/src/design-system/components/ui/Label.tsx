import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface LabelProps extends React.LabelHTMLAttributes<HTMLLabelElement> {
  required?: boolean;
}

export const Label: React.FC<LabelProps> = ({ className, required, children, ...props }) => {
  return (
    <label
      className={cn('text-stone-700 text-sm font-semibold block select-none', className)}
      {...props}
    >
      {children}
      {required && <span className="text-rose-500 ml-1">*</span>}
    </label>
  )
}
