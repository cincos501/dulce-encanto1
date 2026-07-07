import React from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '@/app/providers/AuthContext'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import AccessDenied from '@/modules/auth/pages/AccessDenied'

interface ProtectedRouteProps {
  children: React.ReactNode;
  permission?: string;
}

export default function ProtectedRoute({ children, permission }: ProtectedRouteProps) {
  const { user, loading } = useAuth()
  const { hasPermission } = useAuthorization()

  if (loading) {
    return (
      <div className="min-h-screen bg-stone-50 flex items-center justify-center">
        <div className="flex flex-col items-center gap-3 text-center">
          <svg className="animate-spin h-8 w-8 text-rose-500" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth={4} />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <p className="text-stone-500 text-xs font-semibold">Comprobando permisos y sesión activa...</p>
        </div>
      </div>
    )
  }

  // 1. If user is not authenticated, redirect to login
  if (!user) {
    return <Navigate to="/login" replace />
  }

  // 2. If user is authenticated but doesn't have the required permission, render 403 page
  if (permission && !hasPermission(permission)) {
    return <AccessDenied />
  }

  return <>{children}</>
}
