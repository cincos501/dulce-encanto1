import React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/shared/utils/cn'

const typographyVariants = cva(
  'font-sans text-text-main antialiased',
  {
    variants: {
      variant: {
        h1: 'font-heading font-black text-3xl sm:text-4xl lg:text-5xl leading-tight tracking-tight text-primary',
        h2: 'font-heading font-black text-2xl sm:text-3xl leading-tight tracking-tight text-primary',
        h3: 'font-heading font-bold text-lg sm:text-xl leading-snug text-primary',
        body: 'font-sans text-xs leading-relaxed text-text-sub',
        caption: 'font-sans text-[10px] text-text-sub/70 font-bold uppercase tracking-wider',
        label: 'font-sans text-xs font-bold text-primary'
      }
    },
    defaultVariants: {
      variant: 'body'
    }
  }
)

export interface TypographyProps
  extends React.HTMLAttributes<HTMLElement>,
    VariantProps<typeof typographyVariants> {
  as?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'span' | 'label' | 'div';
}

export const Typography: React.FC<TypographyProps> = ({
  className,
  variant,
  as: Component = 'p',
  ...props
}) => {
  return (
    <Component
      className={cn(typographyVariants({ variant, className }))}
      {...props}
    />
  )
}
