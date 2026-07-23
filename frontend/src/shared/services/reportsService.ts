import api from '@/lib/axios'
import { ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface ReportSummary {
  period_sales: number;
  registered_orders: number;
  products_sold: number;
  registered_customers: number;
  pending_orders: number;
  delivered_orders: number;
  cancelled_orders: number;
  critical_stock: number;
}

export interface SalesReportData {
  total_sales_count: number;
  total_orders_count: number;
  total_revenue: number;
  average_ticket: number;
  status_counts: Record<string, number>;
}

export interface MostSoldProduct {
  product_name: string;
  variant_name: string;
  quantity_sold: number;
  total_generated: number;
}

export interface SupplyReportItem {
  name: string;
  stock: number;
  unit: string;
  minimum_stock: number;
  status: 'Stock suficiente' | 'Stock bajo' | 'Stock crítico';
}

export interface ProductionReportData {
  status_counts: Record<string, number>;
}

const reportsService = {
  /**
   * Get reports dashboard summary.
   */
  async getSummary(startDate: string, endDate: string): Promise<{ data: ApiResponse<ReportSummary> }> {
    return api.get(`/api/v1/reports/summary?start_date=${startDate}&end_date=${endDate}`)
  },

  /**
   * Get sales report metrics and paginated orders.
   */
  async getSales(
    startDate: string,
    endDate: string,
    page: number = 1,
    sortBy: string = 'created_at',
    sortOrder: string = 'desc'
  ): Promise<{ data: ApiResponse<SalesReportData & { orders: ApiPaginateResponse<any> }> }> {
    return api.get(
      `/api/v1/reports/sales?start_date=${startDate}&end_date=${endDate}&page=${page}&sort_by=${sortBy}&sort_order=${sortOrder}`
    )
  },

  /**
   * Get most sold products report.
   */
  async getProducts(startDate: string, endDate: string): Promise<{ data: ApiResponse<MostSoldProduct[]> }> {
    return api.get(`/api/v1/reports/products?start_date=${startDate}&end_date=${endDate}`)
  },

  /**
   * Get supplies report.
   */
  async getSupplies(): Promise<{ data: ApiResponse<SupplyReportItem[]> }> {
    return api.get('/api/v1/reports/supplies')
  },

  /**
   * Get production report metrics and paginated orders list.
   */
  async getProduction(
    startDate: string,
    endDate: string,
    page: number = 1,
    sortBy: string = 'created_at',
    sortOrder: string = 'desc'
  ): Promise<{ data: ApiResponse<ProductionReportData & { orders: ApiPaginateResponse<any> }> }> {
    return api.get(
      `/api/v1/reports/production?start_date=${startDate}&end_date=${endDate}&page=${page}&sort_by=${sortBy}&sort_order=${sortOrder}`
    )
  },

  /**
   * Securely download reports files as authenticated Blob.
   */
  async downloadReportFile(endpointUrl: string, defaultFilename: string): Promise<void> {
    const response = await api.get(endpointUrl, { responseType: 'blob' })
    const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const contentDisposition = response.headers['content-disposition']
    let filename = defaultFilename
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1]
      }
    }

    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    
    // Cleanup
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }
}

export default reportsService
