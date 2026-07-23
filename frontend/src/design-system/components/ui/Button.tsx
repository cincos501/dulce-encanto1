import React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/shared/utils/cn'

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 rounded-lg text-xs font-bold transition-all duration-200 select-none active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-primary/20',
  {
    variants: {
      variant: {
        primary: 'bg-stone-900 text-white hover:bg-black dark:bg-stone-100 dark:text-stone-950 dark:hover:bg-white border border-transparent',
        secondary: 'bg-amber-500 text-stone-950 hover:bg-amber-600 dark:bg-amber-650 dark:text-stone-50 dark:hover:bg-amber-700 border border-transparent',
        ghost: 'text-text-sub hover:bg-stone-100/70 hover:text-primary dark:text-stone-400 dark:hover:bg-stone-800/60 dark:hover:text-stone-200',
        destructive: 'bg-red-650 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-750 border border-transparent',
        info: 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-650 dark:text-white dark:hover:bg-blue-750 border border-transparent',
        neutral: 'bg-stone-200 text-stone-850 hover:bg-stone-300 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700 border border-transparent',
        link: 'text-text-main hover:underline px-0 py-0 rounded-none bg-transparent shadow-none focus:ring-0 active:scale-100'
      },
      size: {
        sm: 'px-3.5 py-1.5 text-[10px] tracking-wider uppercase',
        md: 'px-5 py-2.5 text-xs',
        lg: 'px-6.5 py-3 text-sm'
      }
    },
    defaultVariants: {
      variant: 'primary',
      size: 'md'
    }
  }
)

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  isLoading?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, isLoading, children, ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={cn(buttonVariants({ variant, size, className }))}
        disabled={isLoading || props.disabled}
        {...props}
      >
        {isLoading && (
          <svg className="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-current" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        )}
        {children}
      </button>
    )
  }
)

Button.displayName = 'Button'
