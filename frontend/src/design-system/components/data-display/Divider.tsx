import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface DividerProps extends React.HTMLAttributes<HTMLDivElement> {
  orientation?: 'horizontal' | 'vertical';
}

export const Divider: React.FC<DividerProps> = ({ className, orientation = 'horizontal', ...props }) => {
  return (
    <div
      className={cn(
        'bg-stone-200/60',
        orientation === 'horizontal' ? 'h-[1px] w-full my-4' : 'w-[1px] h-full mx-4',
        className
      )}
      {...props}
    />
  )
}
