import api from '@/lib/axios'
import { Product, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface ProductInput {
  category_id: number;
  name: string;
  description?: string;
  is_active?: boolean;
}

const productsService = {
  /**
   * Get all products (typically non-paginated for selects).
   */
  async getAll(): Promise<{ data: ApiResponse<Product[]> }> {
    return api.get('/api/v1/products?paginate=false')
  },

  /**
   * Get paginated products.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10
  ): Promise<{ data: ApiPaginateResponse<Product> }> {
    return api.get(`/api/v1/products?page=${page}&search=${search}&per_page=${perPage}`)
  },

  /**
   * Find a product by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Product> }> {
    return api.get(`/api/v1/products/${id}`)
  },

  /**
   * Create a new product.
   */
  async create(data: ProductInput): Promise<{ data: ApiResponse<Product> }> {
    return api.post('/api/v1/products', data)
  },

  /**
   * Update an existing product.
   */
  async update(id: number, data: ProductInput): Promise<{ data: ApiResponse<Product> }> {
    return api.put(`/api/v1/products/${id}`, data)
  },

  /**
   * Toggle the active state of a product.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Product> }> {
    return api.patch(`/api/v1/products/${id}/toggle-active`)
  },

  /**
   * Delete a product.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/products/${id}`)
  }
}

export default productsService
