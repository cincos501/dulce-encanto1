import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface SectionTitleProps extends React.HTMLAttributes<HTMLHeadingElement> {
  subtitle?: string;
}

export const SectionTitle: React.FC<SectionTitleProps> = ({ className, subtitle, children, ...props }) => {
  return (
    <div className="space-y-1.5">
      <h2
        className={cn('text-xl font-extrabold text-stone-900 tracking-tight', className)}
        {...props}
      >
        {children}
      </h2>
      {subtitle && (
        <p className="text-stone-500 text-xs leading-normal">{subtitle}</p>
      )}
    </div>
  )
}
