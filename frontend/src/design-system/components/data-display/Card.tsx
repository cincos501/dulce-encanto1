import React from 'react'
import { cn } from '@/shared/utils/cn'

export const Card: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ className, ...props }) => {
  return (
    <div
      className={cn(
        'bg-surface rounded-lg border border-border shadow-sm overflow-hidden',
        className
      )}
      {...props}
    />
  )
}

export const CardHeader: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ className, ...props }) => {
  return (
    <div
      className={cn(
        'border-b border-border/60 p-6 sm:p-7',
        className
      )}
      {...props}
    />
  )
}

export const CardContent: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ className, ...props }) => {
  return (
    <div
      className={cn(
        'p-6 sm:p-7',
        className
      )}
      {...props}
    />
  )
}

export const CardFooter: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({ className, ...props }) => {
  return (
    <div
      className={cn(
        'border-t border-border/60 bg-background/50 p-6 sm:p-7',
        className
      )}
      {...props}
    />
  )
}
