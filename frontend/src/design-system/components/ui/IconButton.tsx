import React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/shared/utils/cn'

const iconButtonVariants = cva(
  'inline-flex items-center justify-center rounded-xl transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-rose-100',
  {
    variants: {
      variant: {
        primary: 'bg-primary text-white hover:bg-stone-900 border border-transparent',
        secondary: 'bg-surface text-text-main border border-border hover:bg-stone-100 dark:hover:bg-stone-800',
        ghost: 'text-text-sub hover:bg-stone-100/70 hover:text-primary dark:text-stone-400 dark:hover:bg-stone-800/60 dark:hover:text-stone-200',
        destructive: 'bg-red-500 hover:bg-red-600 text-white border border-transparent'
      },
      size: {
        sm: 'w-8 h-8 text-xs',
        md: 'w-10 h-10 text-sm',
        lg: 'w-12 h-12 text-base'
      }
    },
    defaultVariants: {
      variant: 'secondary',
      size: 'md'
    }
  }
)

export interface IconButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof iconButtonVariants> {}

export const IconButton = React.forwardRef<HTMLButtonElement, IconButtonProps>(
  ({ className, variant, size, ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={cn(iconButtonVariants({ variant, size, className }))}
        {...props}
      />
    )
  }
)

IconButton.displayName = 'IconButton'
