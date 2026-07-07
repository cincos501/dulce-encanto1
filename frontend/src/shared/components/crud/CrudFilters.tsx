import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface CrudFiltersProps extends React.HTMLAttributes<HTMLDivElement> {}

export const CrudFilters: React.FC<CrudFiltersProps> = ({ className, ...props }) => {
  return (
    <div
      className={cn('flex flex-wrap items-center gap-3 w-full sm:w-auto', className)}
      {...props}
    />
  )
}
