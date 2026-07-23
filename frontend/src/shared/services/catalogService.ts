import api from '@/lib/axios'
import { CatalogItem, CatalogDetail, ApiResponse } from '@/shared/types'

const catalogService = {
  /**
   * Get public active products catalog.
   */
  async getCatalog(): Promise<{ data: ApiResponse<CatalogItem[]> }> {
    return api.get('/api/v1/catalog')
  },

  /**
   * Get single product detail for public view.
   */
  async getProductDetail(id: number): Promise<{ data: ApiResponse<CatalogDetail> }> {
    return api.get(`/api/v1/catalog/${id}`)
  },

  /**
   * Get active promotions with products and variants.
   */
  async getPromotions(): Promise<{ data: ApiResponse<any[]> }> {
    return api.get('/api/v1/catalog/promotions')
  }
}

export default catalogService
