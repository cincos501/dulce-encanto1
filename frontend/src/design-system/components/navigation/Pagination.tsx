import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface PaginationProps extends React.HTMLAttributes<HTMLDivElement> {
  currentPage: number;
  lastPage: number;
  total: number;
  label?: string;
  onPageChange: (page: number) => void;
}

export const Pagination: React.FC<PaginationProps> = ({ 
  className, 
  currentPage, 
  lastPage, 
  total, 
  label = 'elementos',
  onPageChange, 
  ...props 
}) => {
  return (
    <div
      className={cn(
        'bg-background/40 border-t border-border px-6 py-4 flex items-center justify-between gap-4 text-[10px] uppercase tracking-wider font-bold text-text-sub',
        className
      )}
      {...props}
    >
      <div>
        Página {currentPage} de {lastPage} &bull; Total: {total} {label}
      </div>
      <div className="flex gap-2 font-sans">
        <button
          type="button"
          disabled={currentPage === 1}
          onClick={() => onPageChange(Math.max(currentPage - 1, 1))}
          className="bg-surface hover:bg-stone-50 border border-border px-3.5 py-1.5 rounded-lg disabled:opacity-50 disabled:pointer-events-none transition-all duration-150 active:scale-95 text-[10px] uppercase font-bold"
        >
          Anterior
        </button>
        <button
          type="button"
          disabled={currentPage >= lastPage}
          onClick={() => onPageChange(currentPage + 1)}
          className="bg-surface hover:bg-stone-50 border border-border px-3.5 py-1.5 rounded-lg disabled:opacity-50 disabled:pointer-events-none transition-all duration-150 active:scale-95 text-[10px] uppercase font-bold"
        >
          Siguiente
        </button>
      </div>
    </div>
  )
}
