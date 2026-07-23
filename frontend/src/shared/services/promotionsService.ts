import api from '@/lib/axios'
import { Promotion, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface PromotionInput {
  name: string;
  description?: string;
  discount_type: 'percentage' | 'fixed';
  discount: number;
  start_date: string;
  end_date: string;
  is_active?: boolean;
  product_variant_ids?: number[];
}

const promotionsService = {
  /**
   * Get all promotions (typically non-paginated for selects).
   */
  async getAll(onlyActive: boolean = false): Promise<{ data: ApiResponse<Promotion[]> }> {
    const url = onlyActive
      ? '/api/v1/promotions?paginate=false&only_active=true'
      : '/api/v1/promotions?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated promotions.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10
  ): Promise<{ data: ApiPaginateResponse<Promotion> }> {
    return api.get(`/api/v1/promotions?page=${page}&search=${search}&per_page=${perPage}`)
  },

  /**
   * Find a promotion by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Promotion> }> {
    return api.get(`/api/v1/promotions/${id}`)
  },

  /**
   * Create a new promotion.
   */
  async create(data: FormData): Promise<{ data: ApiResponse<Promotion> }> {
    return api.post('/api/v1/promotions', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  /**
   * Update an existing promotion.
   */
  async update(id: number, data: FormData): Promise<{ data: ApiResponse<Promotion> }> {
    data.append('_method', 'PUT')
    return api.post(`/api/v1/promotions/${id}`, data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  /**
   * Toggle the active state of a promotion.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Promotion> }> {
    return api.patch(`/api/v1/promotions/${id}/toggle-active`)
  },

  /**
   * Delete a promotion.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/promotions/${id}`)
  }
}

export default promotionsService
