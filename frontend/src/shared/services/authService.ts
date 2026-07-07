import api from '@/lib/axios'
import { User, ApiResponse } from '@/shared/types'

const authService = {
  /**
   * Fetch CSRF cookie from Laravel Sanctum.
   */
  async csrfCookie(): Promise<unknown> {
    return api.get('/sanctum/csrf-cookie')
  },

  /**
   * Log in the user.
   */
  async login(email: string, password: string): Promise<unknown> {
    return api.post('/api/v1/auth/login', { email, password })
  },

  /**
   * Log out the user.
   */
  async logout(): Promise<unknown> {
    return api.post('/api/v1/auth/logout')
  },

  /**
   * Get the current authenticated user profile.
   */
  async me(): Promise<{ data: ApiResponse<User> }> {
    return api.get('/api/v1/auth/me')
  }
}

export default authService
