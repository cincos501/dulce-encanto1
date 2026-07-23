import api from '@/lib/axios'
import { ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface OrderItem {
  id: number;
  product_name: string;
  presentation_name: string;
  sku: string;
  quantity: number;
  price: number;
  total: number;
}

export interface Order {
  id: number;
  status: 'Pendiente' | 'En preparación' | 'Decorando' | 'Listo' | 'Entregado' | 'Cancelado';
  total: number;
  delivery_date: string | null;
  customer: {
    id: number | null;
    full_name: string;
    email: string | null;
    phone: string;
  };
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}

const ordersService = {
  /**
   * Get paginated orders.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10
  ): Promise<{ data: ApiPaginateResponse<Order> }> {
    return api.get(`/api/v1/orders?page=${page}&search=${search}&per_page=${perPage}`)
  },

  /**
   * Find an order by ID.
   */
  async getById(id: number): Promise<{ data: ApiResponse<Order> }> {
    return api.get(`/api/v1/orders/${id}`)
  },

  /**
   * Update the status of an order.
   */
  async updateStatus(id: number, status: string): Promise<{ data: ApiResponse<Order> }> {
    return api.patch(`/api/v1/orders/${id}/status`, { status })
  },

  /**
   * Submit public checkout order.
   */
  async checkout(data: any): Promise<{ data: ApiResponse<Order> }> {
    return api.post('/api/v1/checkout', data)
  },

  /**
   * Get all customers.
   */
  async getCustomers(): Promise<{ data: ApiResponse<any[]> }> {
    return api.get('/api/v1/orders/customers/list')
  }
}

export default ordersService
