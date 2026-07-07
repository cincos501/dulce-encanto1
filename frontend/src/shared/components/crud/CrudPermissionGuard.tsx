import React from 'react'
import { useAuthorization } from '@/shared/hooks/useAuthorization'

export interface CrudPermissionGuardProps {
  permission?: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export const CrudPermissionGuard: React.FC<CrudPermissionGuardProps> = ({ 
  permission, 
  children, 
  fallback = null 
}) => {
  const { hasPermission } = useAuthorization()

  if (permission && !hasPermission(permission)) {
    return <>{fallback}</>
  }

  return <>{children}</>
}
