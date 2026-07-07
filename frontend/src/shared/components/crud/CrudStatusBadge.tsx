import React from 'react'
import { Badge } from '@/design-system'

export interface CrudStatusBadgeProps {
  isActive: boolean;
  activeLabel?: string;
  inactiveLabel?: string;
  onClick?: () => void;
  disabled?: boolean;
}

export const CrudStatusBadge: React.FC<CrudStatusBadgeProps> = ({
  isActive,
  activeLabel = 'Activo',
  inactiveLabel = 'Inactivo',
  onClick,
  disabled = false
}) => {
  return (
    <button
      type="button"
      disabled={disabled || !onClick}
      onClick={onClick}
      className={`inline-flex outline-none ${
        onClick && !disabled ? 'cursor-pointer hover:opacity-90 active:scale-95 transition-transform' : 'cursor-default pointer-events-none'
      }`}
    >
      <Badge variant={isActive ? 'success' : 'neutral'}>
        {isActive ? activeLabel : inactiveLabel}
      </Badge>
    </button>
  )
}
