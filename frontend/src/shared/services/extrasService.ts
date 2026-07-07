import api from '@/lib/axios'
import { Extra, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface ExtraInput {
  name: string;
  price: number;
  description?: string;
  is_active?: boolean;
}

const extrasService = {
  /**
   * Get all extras (typically non-paginated for select dropdowns).
   */
  async getAll(): Promise<{ data: ApiResponse<Extra[]> }> {
    return api.get('/api/v1/extras?paginate=false')
  },

  /**
   * Get paginated extras.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10
  ): Promise<{ data: ApiPaginateResponse<Extra> }> {
    return api.get(`/api/v1/extras?page=${page}&search=${search}&per_page=${perPage}`)
  },

  /**
   * Find an extra by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Extra> }> {
    return api.get(`/api/v1/extras/${id}`)
  },

  /**
   * Create a new extra.
   */
  async create(data: ExtraInput): Promise<{ data: ApiResponse<Extra> }> {
    return api.post('/api/v1/extras', data)
  },

  /**
   * Update an existing extra.
   */
  async update(id: number, data: ExtraInput): Promise<{ data: ApiResponse<Extra> }> {
    return api.put(`/api/v1/extras/${id}`, data)
  },

  /**
   * Toggle the active state of a extra.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Extra> }> {
    return api.patch(`/api/v1/extras/${id}/toggle-active`)
  },

  /**
   * Delete an extra.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/extras/${id}`)
  }
}

export default extrasService
