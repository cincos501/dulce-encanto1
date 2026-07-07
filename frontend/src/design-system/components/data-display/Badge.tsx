import React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/shared/utils/cn'

const badgeVariants = cva(
  'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] uppercase tracking-wider font-bold border transition-all duration-150',
  {
    variants: {
      variant: {
        success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        danger: 'bg-red-50 text-red-700 border-red-200',
        warning: 'bg-amber-50 text-amber-800 border-amber-200',
        info: 'bg-secondary/15 text-primary border-secondary/30',
        neutral: 'bg-stone-100/80 text-text-sub border-border'
      }
    },
    defaultVariants: {
      variant: 'neutral'
    }
  }
)

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {}

export const Badge: React.FC<BadgeProps> = ({ className, variant, children, ...props }) => {
  return (
    <span
      className={cn(badgeVariants({ variant, className }))}
      {...props}
    >
      <span className={cn(
        'w-1 h-1 rounded-full',
        variant === 'success' && 'bg-emerald-550',
        variant === 'danger' && 'bg-red-500',
        variant === 'warning' && 'bg-amber-500',
        variant === 'info' && 'bg-amber-400',
        variant === 'neutral' && 'bg-stone-400'
      )}></span>
      <span>{children}</span>
    </span>
  )
}
