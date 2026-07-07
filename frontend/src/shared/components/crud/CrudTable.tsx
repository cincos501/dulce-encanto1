import React from 'react'
import { Card, DataTable, Pagination, ColumnDef, EmptyState } from '@/design-system'

export interface CrudTableProps<T> {
  data: T[];
  columns: ColumnDef<T>[];
  isLoading?: boolean;
  emptyIcon?: React.ReactNode;
  emptyTitle?: string;
  emptyDescription?: string;
  
  // Pagination
  currentPage: number;
  lastPage: number;
  total: number;
  onPageChange: (page: number) => void;
  label?: string;
}

export function CrudTable<T>({
  data,
  columns,
  isLoading,
  emptyIcon,
  emptyTitle = 'Sin resultados',
  emptyDescription = 'Aún no se han registrado elementos en este listado.',
  currentPage,
  lastPage,
  total,
  onPageChange,
  label = 'registros'
}: CrudTableProps<T>) {
  return (
    <Card className="shadow-sm">
      <DataTable
        data={data}
        columns={columns}
        isLoading={isLoading}
        emptyElement={
          <EmptyState
            title={emptyTitle}
            description={emptyDescription}
            icon={emptyIcon}
          />
        }
      />
      {data.length > 0 && (
        <Pagination
          currentPage={currentPage}
          lastPage={lastPage}
          total={total}
          label={label}
          onPageChange={onPageChange}
        />
      )}
    </Card>
  )
}
