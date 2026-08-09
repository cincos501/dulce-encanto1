import api from '@/lib/axios'
import { ApiResponse } from '@/shared/types'

export interface ProductionBatchInput {
  product_variant_id: number;
  quantity: number;
  notes?: string;
}

export interface ProductionBatch {
  id: number;
  product_variant_id: number;
  product_variant?: any;
  quantity: number;
  notes: string | null;
  production_date: string;
  created_at: string;
  updated_at: string;
}

const productionService = {
  /**
   * Get paginated production history.
   */
  async paginate(
    page: number = 1,
    perPage: number = 15
  ): Promise<{ data: any }> {
    return api.get(`/api/v1/production?page=${page}&per_page=${perPage}`)
  },

  /**
   * Record a new manual production batch.
   */
  async create(data: ProductionBatchInput): Promise<{ data: ApiResponse<ProductionBatch> }> {
    return api.post('/api/v1/production', data)
  }
}

export default productionService
