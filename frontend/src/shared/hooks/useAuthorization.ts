import { useAuth } from '@/app/providers/AuthContext'

export function useAuthorization() {
  const { user } = useAuth()

  /**
   * Check if user has a specific role.
   */
  const hasRole = (roleName: string): boolean => {
    return user?.roles?.includes(roleName) || false
  }

  /**
   * Check if user has any of the specified roles.
   */
  const hasAnyRole = (rolesArray: string[]): boolean => {
    if (!Array.isArray(rolesArray)) return false
    return user?.roles?.some(r => rolesArray.includes(r)) || false
  }

  /**
   * Check if user has a specific permission.
   */
  const hasPermission = (permissionName: string): boolean => {
    // If the user has 'Administrador' role, they have total access to everything by default
    if (hasRole('Administrador')) return true
    return user?.permissions?.includes(permissionName) || false
  }

  /**
   * Check if user has any of the specified permissions.
   */
  const hasAnyPermission = (permissionsArray: string[]): boolean => {
    if (!Array.isArray(permissionsArray)) return false
    if (hasRole('Administrador')) return true
    return user?.permissions?.some(p => permissionsArray.includes(p)) || false
  }

  return {
    hasRole,
    hasAnyRole,
    hasPermission,
    hasAnyPermission
  }
}
