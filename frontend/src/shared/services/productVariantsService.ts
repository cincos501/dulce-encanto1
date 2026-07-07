import api from '@/lib/axios'
import { ProductVariant, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface ProductVariantInput {
  product_id: number;
  name: string;
  base_price: number;
  is_active?: boolean;
}

const productVariantsService = {
  /**
   * Get all variants (typically non-paginated, optional product_id filter).
   */
  async getAll(productId: number | null = null): Promise<{ data: ApiResponse<ProductVariant[]> }> {
    const url = productId 
      ? `/api/v1/product-variants?paginate=false&product_id=${productId}`
      : '/api/v1/product-variants?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated variants.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10,
    productId: number | null = null
  ): Promise<{ data: ApiPaginateResponse<ProductVariant> }> {
    let url = `/api/v1/product-variants?page=${page}&search=${search}&per_page=${perPage}`
    if (productId !== null) {
      url += `&product_id=${productId}`
    }
    return api.get(url)
  },

  /**
   * Find a variant by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<ProductVariant> }> {
    return api.get(`/api/v1/product-variants/${id}`)
  },

  /**
   * Create a new variant.
   */
  async create(data: ProductVariantInput): Promise<{ data: ApiResponse<ProductVariant> }> {
    return api.post('/api/v1/product-variants', data)
  },

  /**
   * Update an existing variant.
   */
  async update(id: number, data: ProductVariantInput): Promise<{ data: ApiResponse<ProductVariant> }> {
    return api.put(`/api/v1/product-variants/${id}`, data)
  },

  /**
   * Toggle active state.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<ProductVariant> }> {
    return api.patch(`/api/v1/product-variants/${id}/toggle-active`)
  },

  /**
   * Delete a variant.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/product-variants/${id}`)
  }
}

export default productVariantsService
