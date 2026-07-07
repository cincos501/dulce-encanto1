import React from 'react'
import { SearchInput, Button } from '@/design-system'
import { CrudPermissionGuard } from './CrudPermissionGuard'
import { FiPlus } from 'react-icons/fi'

export interface CrudToolbarProps {
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  createLabel?: string;
  createPermission?: string;
  onCreateClick?: () => void;
  extraActions?: React.ReactNode;
}

export const CrudToolbar: React.FC<CrudToolbarProps> = ({
  search,
  onSearchChange,
  searchPlaceholder = 'Buscar...',
  createLabel = 'Nuevo Registro',
  createPermission,
  onCreateClick,
  extraActions
}) => {
  return (
    <div className="bg-surface rounded-lg border border-border p-4 shadow-sm flex flex-col sm:flex-row items-center gap-4 justify-between w-full">
      <div className="flex flex-col sm:flex-row items-center gap-4 flex-grow w-full">
        {/* Search */}
        <div className="w-full sm:max-w-md">
          <SearchInput
            placeholder={searchPlaceholder}
            value={search}
            onChange={(e) => onSearchChange(e.target.value)}
            onClear={() => onSearchChange('')}
          />
        </div>
        {/* Optional Filters */}
        {extraActions && (
          <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            {extraActions}
          </div>
        )}
      </div>
      
      {/* Create Button */}
      {onCreateClick && (
        <CrudPermissionGuard permission={createPermission}>
          <Button
            type="button"
            onClick={onCreateClick}
            className="w-full sm:w-auto flex items-center justify-center gap-1.5"
          >
            <FiPlus className="text-sm shrink-0" />
            <span>{createLabel}</span>
          </Button>
        </CrudPermissionGuard>
      )}
    </div>
  )
}
