import { PageHeader, Button } from '@/design-system'
import { CrudToolbar } from './CrudToolbar'
import { FiPlus } from 'react-icons/fi'

export interface CrudPageProps {
  title: string;
  subtitle?: string;
  createLabel?: string;
  createPermission?: string;
  onCreateClick?: () => void;
  
  // Search
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  
  // Optional filters
  extraActions?: React.ReactNode;
  
  children: React.ReactNode;
}

export const CrudPage: React.FC<CrudPageProps> = ({
  title,
  subtitle,
  createLabel,
  createPermission,
  onCreateClick,
  search,
  onSearchChange,
  searchPlaceholder,
  extraActions,
  children
}) => {
  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <PageHeader
        title={title}
        subtitle={subtitle}
        action={
          onCreateClick && (
            <Button
              onClick={onCreateClick}
              variant="primary"
              size="md"
              className="flex items-center gap-1.5"
            >
              <FiPlus className="text-sm shrink-0" />
              <span>{createLabel || 'Nuevo'}</span>
            </Button>
          )
        }
      />

      {/* Toolbar & Search */}
      <CrudToolbar
        search={search}
        onSearchChange={onSearchChange}
        searchPlaceholder={searchPlaceholder}
        extraActions={extraActions}
      />

      {/* Main Table / Contents Grid */}
      <div className="w-full">
        {children}
      </div>
    </div>
  )
}
