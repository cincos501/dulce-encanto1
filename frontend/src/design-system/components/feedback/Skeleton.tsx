import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'text' | 'rect' | 'circle';
}

export const Skeleton: React.FC<SkeletonProps> = ({ 
  className, 
  variant = 'rect', 
  ...props 
}) => {
  return (
    <div
      className={cn(
        'bg-stone-150 animate-pulse',
        variant === 'text' && 'h-4 w-full rounded-md',
        variant === 'rect' && 'h-24 w-full rounded-xl',
        variant === 'circle' && 'h-12 w-12 rounded-full',
        className
      )}
      {...props}
    />
  )
}
