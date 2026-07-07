import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface PriceTagProps extends React.HTMLAttributes<HTMLDivElement> {
  price: number;
  promoPrice?: number | null;
  currency?: string;
  size?: 'sm' | 'md' | 'lg';
}

export const PriceTag: React.FC<PriceTagProps> = ({ 
  className, 
  price, 
  promoPrice, 
  currency = '$', 
  size = 'md', 
  ...props 
}) => {
  const hasPromo = promoPrice !== undefined && promoPrice !== null

  return (
    <div
      className={cn('inline-flex items-baseline gap-2 font-sans', className)}
      {...props}
    >
      {hasPromo ? (
        <>
          <span className={cn(
            'text-text-sub/50 line-through font-medium font-sans',
            size === 'sm' && 'text-[10px]',
            size === 'md' && 'text-xs',
            size === 'lg' && 'text-sm'
          )}>
            {currency}{Number(price).toFixed(2)}
          </span>
          <span className={cn(
            'font-bold text-red-650 font-sans',
            size === 'sm' && 'text-sm',
            size === 'md' && 'text-lg',
            size === 'lg' && 'text-xl'
          )}>
            {currency}{Number(promoPrice).toFixed(2)}
          </span>
        </>
      ) : (
        <span className={cn(
          'font-bold text-text-main font-sans',
          size === 'sm' && 'text-xs',
          size === 'md' && 'text-base',
          size === 'lg' && 'text-xl'
        )}>
          {currency}{Number(price).toFixed(2)}
        </span>
      )}
    </div>
  )
}
