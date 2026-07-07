import React, { createContext, useContext, useState, useEffect } from 'react'
import authService from '@/shared/services/authService'
import { User } from '@/shared/types'

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<User>;
  logout: () => Promise<void>;
  checkUserSession: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState<boolean>(true)

  // Fetch CSRF cookie, then fetch profile
  const checkUserSession = async () => {
    try {
      const response = await authService.me()
      if (response.data?.success && response.data?.data) {
        setUser(response.data.data)
      } else {
        setUser(null)
      }
    } catch (error) {
      setUser(null)
    } finally {
      setLoading(false)
    }
  }

  // Initial mount: Check session
  useEffect(() => {
    checkUserSession()
  }, [])

  // Hook global logouts to prevent duplicate token issues
  useEffect(() => {
    (window as any).__logoutHandler = async () => {
      setUser(null)
    }
    return () => {
      delete (window as any).__logoutHandler
    }
  }, [])

  const login = async (email: string, password: string): Promise<User> => {
    setLoading(true)
    try {
      // 1. Get CSRF Cookie
      await authService.csrfCookie()
      // 2. Perform Login Request
      await authService.login(email, password)
      // 3. Get Authenticated User Details
      const response = await authService.me()
      const loggedUser = response.data.data
      setUser(loggedUser)
      return loggedUser
    } catch (err) {
      setUser(null)
      throw err
    } finally {
      setLoading(false)
    }
  }

  const logout = async () => {
    setLoading(true)
    try {
      await authService.logout()
    } catch (err) {
      // Allow local logout anyway
    } finally {
      setUser(null)
      setLoading(false)
    }
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, checkUserSession }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth debe usarse dentro de un AuthProvider')
  }
  return context
}
