import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface LoadingProps extends React.HTMLAttributes<HTMLDivElement> {
  label?: string;
}

export const Loading: React.FC<LoadingProps> = ({ className, label = 'Cargando datos...', ...props }) => {
  return (
    <div
      className={cn('py-16 flex flex-col items-center justify-center gap-3 w-full', className)}
      {...props}
    >
      <svg className="animate-spin h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24">
        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <span className="text-text-sub text-xs font-bold tracking-wide animate-pulse">{label}</span>
    </div>
  )
}
