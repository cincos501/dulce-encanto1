import api from '@/lib/axios'
import { Supply, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface SupplyInput {
  name: string;
  unit: string;
  stock: number;
  minimum_stock: number;
  average_cost: number;
  is_active?: boolean;
  suppliers?: {
    supplier_id: number;
    purchase_price: number;
  }[];
}

const suppliesService = {
  /**
   * Get all supplies.
   */
  async getAll(onlyActive: boolean = false): Promise<{ data: ApiResponse<Supply[]> }> {
    const url = onlyActive 
      ? '/api/v1/supplies?paginate=false&only_active=true' 
      : '/api/v1/supplies?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated supplies.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10,
    onlyActive: boolean = false
  ): Promise<{ data: ApiPaginateResponse<Supply> }> {
    const url = onlyActive 
      ? `/api/v1/supplies?page=${page}&search=${search}&per_page=${perPage}&only_active=true`
      : `/api/v1/supplies?page=${page}&search=${search}&per_page=${perPage}`
    return api.get(url)
  },

  /**
   * Find a supply by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Supply> }> {
    return api.get(`/api/v1/supplies/${id}`)
  },

  /**
   * Create a new supply.
   */
  async create(data: SupplyInput): Promise<{ data: ApiResponse<Supply> }> {
    return api.post('/api/v1/supplies', data)
  },

  /**
   * Update an existing supply.
   */
  async update(id: number, data: SupplyInput): Promise<{ data: ApiResponse<Supply> }> {
    return api.put(`/api/v1/supplies/${id}`, data)
  },

  /**
   * Toggle the active state of a supply.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Supply> }> {
    return api.patch(`/api/v1/supplies/${id}/toggle-active`)
  },

  /**
   * Register a supply purchase from a supplier.
   */
  async registerPurchase(payload: {
    supplier_id: number;
    items: { supply_id: number; quantity: number; purchase_price: number }[];
  }): Promise<{ data: ApiResponse<any> }> {
    return api.post('/api/v1/supplies/purchase', payload)
  }
}

export default suppliesService
