import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface ColumnDef<T> {
  header: string;
  cell: (item: T) => React.ReactNode;
  headerClassName?: string;
  cellClassName?: string;
}

export interface DataTableProps<T> extends React.TableHTMLAttributes<HTMLTableElement> {
  data: T[];
  columns: ColumnDef<T>[];
  isLoading?: boolean;
  emptyElement?: React.ReactNode;
}

export function DataTable<T>({ 
  className, 
  data, 
  columns, 
  isLoading, 
  emptyElement, 
  ...props 
}: DataTableProps<T>) {
  return (
    <div className="overflow-x-auto w-full">
      <table 
        className={cn('w-full text-left border-collapse', className)}
        {...props}
      >
        <thead>
          <tr className="bg-background border-b border-border/80 text-[10px] uppercase font-bold tracking-widest text-text-sub">
            {columns.map((col, index) => (
              <th 
                key={index} 
                className={cn('px-6 py-3.5', col.headerClassName)}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-border/40 text-xs text-text-main">
          {isLoading ? (
            <tr>
              <td colSpan={columns.length} className="py-16 text-center">
                <div className="flex flex-col items-center justify-center gap-2">
                  <svg className="animate-spin h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span className="text-text-sub text-[10px] uppercase font-bold tracking-wide animate-pulse">Cargando registros...</span>
                </div>
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td colSpan={columns.length} className="py-12 text-center">
                {emptyElement || (
                  <div className="text-text-sub/50 text-xs italic">
                    No se encontraron registros.
                  </div>
                )}
              </td>
            </tr>
          ) : (
            data.map((item, rowIndex) => (
              <tr 
                key={rowIndex} 
                className="hover:bg-stone-50/50 transition-colors"
              >
                {columns.map((col, colIndex) => (
                  <td 
                    key={colIndex} 
                    className={cn('px-6 py-3.5 font-medium', col.cellClassName)}
                  >
                    {col.cell(item)}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
