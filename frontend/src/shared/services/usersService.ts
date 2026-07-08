import api from '@/lib/axios'
import { User, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface UserInput {
  full_name: string;
  email: string;
  phone?: string | null;
  role?: string;
  is_active?: boolean;
  password?: string;
}

const usersService = {
  /**
   * Get paginated users with filters.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10,
    role: string = '',
    status: string = ''
  ): Promise<{ data: ApiPaginateResponse<User> }> {
    let url = `/api/v1/users?page=${page}&search=${search}&per_page=${perPage}`
    if (role) url += `&role=${role}`
    if (status) url += `&status=${status}`
    return api.get(url)
  },

  /**
   * Find user by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<User> }> {
    return api.get(`/api/v1/users/${id}`)
  },

  /**
   * Create a new user.
   */
  async create(data: UserInput): Promise<{ data: ApiResponse<User> }> {
    return api.post('/api/v1/users', data)
  },

  /**
   * Update an existing user.
   */
  async update(id: number, data: UserInput): Promise<{ data: ApiResponse<User> }> {
    return api.put(`/api/v1/users/${id}`, data)
  },

  /**
   * Reset a user's password.
   */
  async resetPassword(id: number, data: { password?: string }): Promise<{ data: ApiResponse<null> }> {
    return api.post(`/api/v1/users/${id}/reset-password`, data)
  },

  /**
   * Toggle user active status.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<User> }> {
    return api.patch(`/api/v1/users/${id}/toggle-active`)
  },

  /**
   * Delete a user.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/users/${id}`)
  }
}

export default usersService
