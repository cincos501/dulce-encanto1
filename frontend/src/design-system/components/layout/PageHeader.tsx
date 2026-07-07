import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface PageHeaderProps extends React.HTMLAttributes<HTMLDivElement> {
  title: string;
  subtitle?: string;
  action?: React.ReactNode;
}

export const PageHeader: React.FC<PageHeaderProps> = ({ 
  className, 
  title, 
  subtitle, 
  action, 
  ...props 
}) => {
  return (
    <div
      className={cn(
        'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface rounded-3xl border border-border p-6 sm:p-8 shadow-sm',
        className
      )}
      {...props}
    >
      <div className="space-y-1">
        <h1 className="text-3xl font-extrabold text-primary tracking-tight">{title}</h1>
        {subtitle && (
          <p className="text-text-sub text-sm leading-normal">{subtitle}</p>
        )}
      </div>
      {action && (
        <div className="self-start sm:self-center flex items-center">
          {action}
        </div>
      )}
    </div>
  )
}
