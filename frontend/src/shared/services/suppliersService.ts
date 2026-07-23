import api from '@/lib/axios'
import { Supplier, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface SupplierInput {
  business_name: string;
  phone: string;
  email?: string | null;
  address?: string | null;
  is_active?: boolean;
}

const suppliersService = {
  /**
   * Get all suppliers.
   */
  async getAll(onlyActive: boolean = false): Promise<{ data: ApiResponse<Supplier[]> }> {
    const url = onlyActive 
      ? '/api/v1/suppliers?paginate=false&only_active=true' 
      : '/api/v1/suppliers?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated suppliers.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10,
    onlyActive: boolean = false
  ): Promise<{ data: ApiPaginateResponse<Supplier> }> {
    const url = onlyActive 
      ? `/api/v1/suppliers?page=${page}&search=${search}&per_page=${perPage}&only_active=true`
      : `/api/v1/suppliers?page=${page}&search=${search}&per_page=${perPage}`
    return api.get(url)
  },

  /**
   * Find a supplier by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Supplier> }> {
    return api.get(`/api/v1/suppliers/${id}`)
  },

  /**
   * Create a new supplier.
   */
  async create(data: SupplierInput): Promise<{ data: ApiResponse<Supplier> }> {
    return api.post('/api/v1/suppliers', data)
  },

  /**
   * Update an existing supplier.
   */
  async update(id: number, data: SupplierInput): Promise<{ data: ApiResponse<Supplier> }> {
    return api.put(`/api/v1/suppliers/${id}`, data)
  },

  /**
   * Toggle the active state of a supplier.
   */
  async toggleActive(id: number): Promise<{ data: ApiResponse<Supplier> }> {
    return api.patch(`/api/v1/suppliers/${id}/toggle-active`)
  }
}

export default suppliersService
