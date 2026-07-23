import api from '@/lib/axios'
import { Category, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface CategoryInput {
  name: string;
  description?: string;
  is_active?: boolean;
}

const categoriesService = {
  /**
   * Get all categories (typically non-paginated for select dropdowns).
   */
  async getAll(onlyActive: boolean = false): Promise<{ data: ApiResponse<Category[]> }> {
    const url = onlyActive 
      ? '/api/v1/categories?paginate=false&only_active=true' 
      : '/api/v1/categories?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated categories.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10
  ): Promise<{ data: ApiPaginateResponse<Category> }> {
    return api.get(`/api/v1/categories?page=${page}&search=${search}&per_page=${perPage}`)
  },

  /**
   * Find a category by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Category> }> {
    return api.get(`/api/v1/categories/${id}`)
  },

  /**
   * Create a new category.
   */
  async create(data: CategoryInput): Promise<{ data: ApiResponse<Category> }> {
    return api.post('/api/v1/categories', data)
  },

  /**
   * Update an existing category.
   */
  async update(id: number, data: CategoryInput): Promise<{ data: ApiResponse<Category> }> {
    return api.put(`/api/v1/categories/${id}`, data)
  },

  /**
   * Toggle the active state of a category.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Category> }> {
    return api.patch(`/api/v1/categories/${id}/toggle-active`)
  },

  /**
   * Delete a category.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/categories/${id}`)
  }
}

export default categoriesService
