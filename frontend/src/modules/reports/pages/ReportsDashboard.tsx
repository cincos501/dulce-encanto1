import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { toast } from 'sonner'
import { 
  StatCard, 
  Card, 
  CardHeader, 
  CardContent, 
  Badge, 
  Typography, 
  Button, 
  Divider, 
  Input 
} from '@/design-system'
import { 
  FiDollarSign, 
  FiShoppingBag, 
  FiPackage, 
  FiLayers, 
  FiBarChart2, 
  FiFileText, 
  FiGrid, 
  FiCalendar, 
  FiTrendingUp, 
  FiArrowRight, 
  FiDownload,
  FiUser
} from 'react-icons/fi'
import reportsService from '@/shared/services/reportsService'
import { cn } from '@/shared/utils/cn'

type TabType = 'summary' | 'sales' | 'products' | 'supplies' | 'production'

export default function ReportsDashboard() {
  // Global filter state
  const [startDate, setStartDate] = useState<string>(() => {
    const d = new Date()
    return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0]
  })
  const [endDate, setEndDate] = useState<string>(() => {
    return new Date().toISOString().split('T')[0]
  })

  const [activeTab, setActiveTab] = useState<TabType>('summary')
  const [isExporting, setIsExporting] = useState<boolean>(false)

  // Pagination and sorting for detail lists
  const [salesPage, setSalesPage] = useState<number>(1)
  const [productionPage, setProductionPage] = useState<number>(1)

  // Query: Summary Metrics
  const { data: summary, isLoading: isSummaryLoading } = useQuery({
    queryKey: ['reports-summary', startDate, endDate],
    queryFn: async () => {
      const response = await reportsService.getSummary(startDate, endDate)
      return response.data?.data
    },
    enabled: activeTab === 'summary'
  })

  // Query: Sales Report
  const { data: salesReport, isLoading: isSalesLoading } = useQuery({
    queryKey: ['reports-sales', startDate, endDate, salesPage],
    queryFn: async () => {
      const response = await reportsService.getSales(startDate, endDate, salesPage)
      return response.data?.data
    },
    enabled: activeTab === 'sales'
  })

  // Query: Products Report
  const { data: productsReport = [], isLoading: isProductsLoading } = useQuery({
    queryKey: ['reports-products', startDate, endDate],
    queryFn: async () => {
      const response = await reportsService.getProducts(startDate, endDate)
      return response.data?.data || []
    },
    enabled: activeTab === 'products'
  })

  // Query: Supplies Report
  const { data: suppliesReport = [], isLoading: isSuppliesLoading } = useQuery({
    queryKey: ['reports-supplies'],
    queryFn: async () => {
      const response = await reportsService.getSupplies()
      return response.data?.data || []
    },
    enabled: activeTab === 'supplies'
  })

  // Query: Production Report
  const { data: productionReport, isLoading: isProductionLoading } = useQuery({
    queryKey: ['reports-production', startDate, endDate, productionPage],
    queryFn: async () => {
      const response = await reportsService.getProduction(startDate, endDate, productionPage)
      return response.data?.data
    },
    enabled: activeTab === 'production'
  })

  // Export handlers
  const handleExport = async (type: 'pdf' | 'excel', reportName: string) => {
    setIsExporting(true)
    try {
      let url = ''
      let defaultFilename = ''
      if (reportName === 'sales') {
        url = type === 'pdf'
          ? `/api/v1/reports/sales/export-pdf?start_date=${startDate}&end_date=${endDate}`
          : `/api/v1/reports/sales/export-excel?start_date=${startDate}&end_date=${endDate}`
        defaultFilename = `reporte_ventas.${type === 'pdf' ? 'pdf' : 'csv'}`
      } else if (reportName === 'products') {
        url = type === 'pdf'
          ? `/api/v1/reports/products/export-pdf?start_date=${startDate}&end_date=${endDate}`
          : `/api/v1/reports/products/export-excel?start_date=${startDate}&end_date=${endDate}`
        defaultFilename = `reporte_productos_mas_vendidos.${type === 'pdf' ? 'pdf' : 'csv'}`
      } else if (reportName === 'supplies') {
        url = type === 'pdf'
          ? `/api/v1/reports/supplies/export-pdf`
          : `/api/v1/reports/supplies/export-excel`
        defaultFilename = `reporte_insumos.${type === 'pdf' ? 'pdf' : 'csv'}`
      } else if (reportName === 'production') {
        url = type === 'pdf'
          ? `/api/v1/reports/production/export-pdf?start_date=${startDate}&end_date=${endDate}`
          : `/api/v1/reports/production/export-excel?start_date=${startDate}&end_date=${endDate}`
        defaultFilename = `reporte_produccion.${type === 'pdf' ? 'pdf' : 'csv'}`
      }

      await reportsService.downloadReportFile(url, defaultFilename)
      toast.success('Reporte exportado con éxito.')
    } catch (err: any) {
      toast.error('Error al exportar el reporte.')
    } finally {
      setIsExporting(false)
    }
  }

  // Helpers
  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case 'Pendiente': return 'warning'
      case 'Confirmado': return 'neutral'
      case 'En preparación': return 'info'
      case 'Listo': return 'success'
      case 'Entregado': return 'success'
      case 'Cancelado': return 'danger'
      default: return 'neutral'
    }
  }

  const getSupplyBadgeVariant = (status: string) => {
    switch (status) {
      case 'Stock crítico': return 'danger'
      case 'Stock bajo': return 'warning'
      case 'Stock suficiente': return 'success'
      default: return 'neutral'
    }
  }

  const parseDeliveryDetails = (emailStr: string | null) => {
    if (!emailStr) return 'Retiro en tienda'
    try {
      if (emailStr.trim().startsWith('{')) {
        const parsed = JSON.parse(emailStr)
        return parsed.delivery_type || 'Retiro en tienda'
      }
    } catch {
      // Ignore
    }
    return 'Retiro en tienda'
  }

  return (
    <div className="space-y-6 font-sans">
      
      {/* HEADER SECTION */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Typography variant="h1" className="text-3xl font-black text-primary">
            Reportes Administrativos
          </Typography>
          <p className="text-xs text-text-sub font-semibold mt-1">
            Visualiza métricas, analiza la producción, el inventario y exporta reportes detallados del negocio.
          </p>
        </div>

        {/* Global Date Filter Inputs */}
        <div className="flex items-center gap-2.5 bg-surface border border-border p-2.5 rounded-lg shadow-sm">
          <FiCalendar className="text-text-sub shrink-0 text-xs" />
          <input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="text-xs font-bold bg-transparent text-text-main border-none focus:ring-0 w-28 p-0 cursor-pointer"
          />
          <FiArrowRight className="text-text-sub text-[10px]" />
          <input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="text-xs font-bold bg-transparent text-text-main border-none focus:ring-0 w-28 p-0 cursor-pointer"
          />
        </div>
      </div>

      {/* TABS NAVIGATION */}
      <div className="flex border-b border-border gap-1 overflow-x-auto select-none">
        {(['summary', 'sales', 'products', 'supplies', 'production'] as const).map((tab) => {
          const labels: Record<TabType, string> = {
            summary: 'Resumen',
            sales: 'Ventas',
            products: 'Productos más vendidos',
            supplies: 'Control de Insumos',
            production: 'Control de Producción'
          }
          const icons: Record<TabType, React.ReactNode> = {
            summary: <FiGrid className="text-sm" />,
            sales: <FiDollarSign className="text-sm" />,
            products: <FiTrendingUp className="text-sm" />,
            supplies: <FiLayers className="text-sm" />,
            production: <FiShoppingBag className="text-sm" />
          }
          const isActive = activeTab === tab
          return (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={cn(
                "flex items-center gap-2 px-5 py-3 border-b-2 font-bold text-xs transition-all whitespace-nowrap cursor-pointer",
                isActive
                  ? "border-primary text-primary font-black bg-secondary/5"
                  : "border-transparent text-text-sub hover:text-primary hover:border-stone-300"
              )}
            >
              {icons[tab]}
              <span>{labels[tab]}</span>
            </button>
          )
        })}
      </div>

      {/* TAB CONTENT: SUMMARY */}
      {activeTab === 'summary' && (
        <div className="space-y-6 animate-fade-in">
          {isSummaryLoading ? (
            <p className="text-center py-10 font-bold text-xs text-text-sub italic">Cargando resumen de métricas...</p>
          ) : summary ? (
            <>
              {/* Metrics Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                  title="Ventas del Período"
                  value={`Bs. ${summary.period_sales.toFixed(2)}`}
                  change="Ventas netas sin cancelados"
                  changeType="neutral"
                  icon={<FiDollarSign />}
                />
                <StatCard
                  title="Pedidos Registrados"
                  value={String(summary.registered_orders)}
                  change="Total de pedidos ingresados"
                  changeType="neutral"
                  icon={<FiShoppingBag />}
                />
                <StatCard
                  title="Productos Vendidos"
                  value={`${summary.products_sold} u`}
                  change="Unidades físicas vendidas"
                  changeType="neutral"
                  icon={<FiPackage />}
                />
                <StatCard
                  title="Clientes Registrados"
                  value={String(summary.registered_customers)}
                  change="Base total de clientes en sistema"
                  changeType="neutral"
                  icon={<FiUser />}
                />
              </div>

              {/* Order Status Cards */}
              <Card>
                <CardHeader>
                  <Typography variant="h3">Resumen de Estados de Pedido</Typography>
                  <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Detalle del total del periodo por estados de entrega.</p>
                </CardHeader>
                <CardContent className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg text-center">
                    <span className="text-[10px] text-text-sub uppercase font-bold tracking-widest block mb-1">Pendientes</span>
                    <span className="font-heading font-black text-xl text-yellow-650">{summary.pending_orders}</span>
                  </div>
                  <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg text-center">
                    <span className="text-[10px] text-text-sub uppercase font-bold tracking-widest block mb-1">Entregados</span>
                    <span className="font-heading font-black text-xl text-green-650">{summary.delivered_orders}</span>
                  </div>
                  <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg text-center">
                    <span className="text-[10px] text-text-sub uppercase font-bold tracking-widest block mb-1">Cancelados</span>
                    <span className="font-heading font-black text-xl text-red-650">{summary.cancelled_orders}</span>
                  </div>
                  <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg text-center">
                    <span className="text-[10px] text-text-sub uppercase font-bold tracking-widest block mb-1">Stock Crítico</span>
                    <span className="font-heading font-black text-xl text-red-650">{summary.critical_stock} insumos</span>
                  </div>
                </CardContent>
              </Card>
            </>
          ) : (
            <p className="text-center py-10 font-semibold text-text-sub">No se pudieron recuperar las métricas.</p>
          )}
        </div>
      )}

      {/* TAB CONTENT: SALES */}
      {activeTab === 'sales' && (
        <div className="space-y-6 animate-fade-in">
          {/* Export bar */}
          <div className="flex justify-end gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleExport('excel', 'sales')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiDownload className="text-xs" />
              <span>Exportar Excel</span>
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => handleExport('pdf', 'sales')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiFileText className="text-xs" />
              <span>Exportar PDF</span>
            </Button>
          </div>

          {isSalesLoading ? (
            <p className="text-center py-10 font-bold text-xs text-text-sub italic">Cargando reporte de ventas...</p>
          ) : salesReport ? (
            <>
              {/* Sales Indicators Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                  title="Ingreso de Ventas"
                  value={`Bs. ${salesReport.total_revenue.toFixed(2)}`}
                  change="Total de ventas facturadas"
                  changeType="neutral"
                  icon={<FiDollarSign />}
                />
                <StatCard
                  title="Total Pedidos"
                  value={String(salesReport.total_orders_count)}
                  change="Cantidad total de compras"
                  changeType="neutral"
                  icon={<FiShoppingBag />}
                />
                <StatCard
                  title="Ventas Completadas"
                  value={String(salesReport.total_sales_count)}
                  change="Pedidos entregados con éxito"
                  changeType="neutral"
                  icon={<FiBarChart2 />}
                />
                <StatCard
                  title="Ticket Promedio"
                  value={`Bs. ${salesReport.average_ticket.toFixed(2)}`}
                  change="Ingreso medio por pedido"
                  changeType="neutral"
                  icon={<FiTrendingUp />}
                />
              </div>

              {/* Detail Table */}
              <Card>
                <CardHeader>
                  <Typography variant="h3">Detalle de Pedidos de Venta</Typography>
                  <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Historial detallado de pedidos facturados en el periodo.</p>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-stone-50 dark:bg-stone-900 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                          <th className="px-6 py-3">Código</th>
                          <th className="px-6 py-3">Cliente</th>
                          <th className="px-6 py-3">Fecha</th>
                          <th className="px-6 py-3">Estado</th>
                          <th className="px-6 py-3">Tipo Entrega</th>
                          <th className="px-6 py-3 text-right">Total</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/60 text-text-main font-semibold">
                        {salesReport.orders?.data?.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="px-6 py-8 text-center text-text-sub italic select-none">
                              No hay pedidos en el rango seleccionado.
                            </td>
                          </tr>
                        ) : (
                          salesReport.orders?.data?.map((o: any) => (
                            <tr key={o.id} className="hover:bg-stone-50/50 transition-colors">
                              <td className="px-6 py-4 font-mono font-bold text-text-sub/70">#{o.id}</td>
                              <td className="px-6 py-4 font-bold text-primary">{o.customer?.full_name}</td>
                              <td className="px-6 py-4 font-sans font-semibold text-text-sub">{new Date(o.created_at).toLocaleString()}</td>
                              <td className="px-6 py-4">
                                <Badge variant={getStatusBadgeVariant(o.status)}>
                                  {o.status}
                                </Badge>
                              </td>
                              <td className="px-6 py-4 text-text-sub">{parseDeliveryDetails(o.customer?.email)}</td>
                              <td className="px-6 py-4 text-right font-bold text-primary">Bs. {Number(o.total).toFixed(2)}</td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>

                  {/* Pagination control */}
                  {salesReport.orders?.meta && salesReport.orders.meta.last_page > 1 && (
                    <div className="flex items-center justify-between p-4 border-t border-border bg-stone-50 dark:bg-stone-900">
                      <span className="text-[10px] text-text-sub font-bold">
                        Página {salesReport.orders.meta.current_page} de {salesReport.orders.meta.last_page}
                      </span>
                      <div className="flex gap-2">
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={salesPage === 1}
                          onClick={() => setSalesPage(prev => Math.max(1, prev - 1))}
                        >
                          Anterior
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={salesPage === salesReport.orders.meta.last_page}
                          onClick={() => setSalesPage(prev => prev + 1)}
                        >
                          Siguiente
                        </Button>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            </>
          ) : (
            <p className="text-center py-10 font-semibold text-text-sub">No hay registros de ventas para mostrar.</p>
          )}
        </div>
      )}

      {/* TAB CONTENT: PRODUCTS */}
      {activeTab === 'products' && (
        <div className="space-y-6 animate-fade-in">
          {/* Export bar */}
          <div className="flex justify-end gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleExport('excel', 'products')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiDownload className="text-xs" />
              <span>Exportar Excel</span>
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => handleExport('pdf', 'products')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiFileText className="text-xs" />
              <span>Exportar PDF</span>
            </Button>
          </div>

          {isProductsLoading ? (
            <p className="text-center py-10 font-bold text-xs text-text-sub italic">Cargando ranking de productos...</p>
          ) : (
            <Card>
              <CardHeader>
                <Typography variant="h3">Ranking de Productos Más Vendidos</Typography>
                <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Productos y presentaciones con mayor demanda ordenados de mayor a menor.</p>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse text-xs">
                    <thead>
                      <tr className="bg-stone-50 dark:bg-stone-900 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                        <th className="px-6 py-3 w-12 text-center">Posición</th>
                        <th className="px-6 py-3">Producto</th>
                        <th className="px-6 py-3">Presentación / Tamaño</th>
                        <th className="px-6 py-3 text-center w-28">Cant. Vendida</th>
                        <th className="px-6 py-3 text-right w-36">Total Generado</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border/60 text-text-main font-semibold">
                      {productsReport.length === 0 ? (
                        <tr>
                          <td colSpan={5} className="px-6 py-8 text-center text-text-sub italic select-none">
                            No hay productos registrados en este periodo.
                          </td>
                        </tr>
                      ) : (
                        productsReport.map((p, index) => (
                          <tr key={index} className="hover:bg-stone-50/50 transition-colors">
                            <td className="px-6 py-4 text-center font-bold text-text-sub/65">{index + 1}</td>
                            <td className="px-6 py-4 font-bold text-primary">{p.product_name}</td>
                            <td className="px-6 py-4 text-text-sub">{p.variant_name}</td>
                            <td className="px-6 py-4 text-center font-bold text-text-main">{p.quantity_sold} u</td>
                            <td className="px-6 py-4 text-right font-bold text-primary">Bs. {Number(p.total_generated).toFixed(2)}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      )}

      {/* TAB CONTENT: SUPPLIES */}
      {activeTab === 'supplies' && (
        <div className="space-y-6 animate-fade-in">
          {/* Export bar */}
          <div className="flex justify-end gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleExport('excel', 'supplies')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiDownload className="text-xs" />
              <span>Exportar Excel</span>
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => handleExport('pdf', 'supplies')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiFileText className="text-xs" />
              <span>Exportar PDF</span>
            </Button>
          </div>

          {isSuppliesLoading ? (
            <p className="text-center py-10 font-bold text-xs text-text-sub italic">Cargando control de insumos...</p>
          ) : (
            <Card>
              <CardHeader>
                <Typography variant="h3">Control de Stock y Alertas de Insumos</Typography>
                <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Control semafórico del nivel de materias primas e ingredientes en tiempo real.</p>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse text-xs">
                    <thead>
                      <tr className="bg-stone-50 dark:bg-stone-900 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                        <th className="px-6 py-3">Insumo</th>
                        <th className="px-6 py-3 text-right w-28">Stock Actual</th>
                        <th className="px-6 py-3 text-center w-24">Unidad</th>
                        <th className="px-6 py-3 text-right w-28">Stock Mínimo</th>
                        <th className="px-6 py-3 text-center w-40">Estado de Alerta</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border/60 text-text-main font-semibold">
                      {suppliesReport.length === 0 ? (
                        <tr>
                          <td colSpan={5} className="px-6 py-8 text-center text-text-sub italic select-none">
                            No hay insumos registrados en el sistema.
                          </td>
                        </tr>
                      ) : (
                        suppliesReport.map((s, index) => (
                          <tr key={index} className="hover:bg-stone-50/50 transition-colors">
                            <td className="px-6 py-4 font-bold text-primary">{s.name}</td>
                            <td className="px-6 py-4 text-right font-mono font-bold text-text-main">{s.stock.toFixed(2)}</td>
                            <td className="px-6 py-4 text-center text-text-sub">{s.unit}</td>
                            <td className="px-6 py-4 text-right font-mono text-text-sub">{s.minimum_stock.toFixed(2)}</td>
                            <td className="px-6 py-4 text-center">
                              <Badge variant={getSupplyBadgeVariant(s.status)}>
                                {s.status}
                              </Badge>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      )}

      {/* TAB CONTENT: PRODUCTION */}
      {activeTab === 'production' && (
        <div className="space-y-6 animate-fade-in">
          {/* Export bar */}
          <div className="flex justify-end gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleExport('excel', 'production')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiDownload className="text-xs" />
              <span>Exportar Excel</span>
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => handleExport('pdf', 'production')}
              disabled={isExporting}
              className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]"
            >
              <FiFileText className="text-xs" />
              <span>Exportar PDF</span>
            </Button>
          </div>

          {isProductionLoading ? (
            <p className="text-center py-10 font-bold text-xs text-text-sub italic">Cargando control de producción...</p>
          ) : productionReport ? (
            <>
              {/* Production counts */}
              <div className="grid grid-cols-2 sm:grid-cols-6 gap-4">
                {Object.entries(productionReport.status_counts).map(([status, count]) => {
                  const label = status.replace('_', ' ')
                  const formattedLabel = label.charAt(0).toUpperCase() + label.slice(1)
                  return (
                    <div key={status} className="bg-stone-50 dark:bg-stone-900 border border-border p-3.5 rounded-lg text-center">
                      <span className="text-[9px] text-text-sub font-bold uppercase tracking-wider block mb-0.5">{formattedLabel}</span>
                      <span className="font-heading font-black text-lg text-primary">{count}</span>
                    </div>
                  )
                })}
              </div>

              {/* Detailed list */}
              <Card>
                <CardHeader>
                  <Typography variant="h3">Detalle de Pedidos en Producción</Typography>
                  <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Control de entrega de pedidos y volumen físico de productos asociados.</p>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-stone-50 dark:bg-stone-900 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                          <th className="px-6 py-3 w-28">Nº Pedido</th>
                          <th className="px-6 py-3">Fecha</th>
                          <th className="px-6 py-3">Cliente</th>
                          <th className="px-6 py-3 text-center w-36">Cant. de Productos</th>
                          <th className="px-6 py-3 w-36">Estado</th>
                          <th className="px-6 py-3 w-36">Tipo Entrega</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/60 text-text-main font-semibold">
                        {productionReport.orders?.data?.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="px-6 py-8 text-center text-text-sub italic select-none">
                              No hay pedidos registrados en este rango.
                            </td>
                          </tr>
                        ) : (
                          productionReport.orders?.data?.map((o: any) => {
                            const qty = o.items ? o.items.reduce((sum: number, i: any) => sum + i.quantity, 0) : 0
                            return (
                              <tr key={o.id} className="hover:bg-stone-50/50 transition-colors">
                                <td className="px-6 py-4 font-mono font-bold text-text-sub/70">#{o.id}</td>
                                <td className="px-6 py-4 font-sans text-text-sub">{new Date(o.created_at).toLocaleDateString()}</td>
                                <td className="px-6 py-4 font-bold text-primary">{o.customer?.full_name}</td>
                                <td className="px-6 py-4 text-center font-bold text-text-main">{qty} u</td>
                                <td className="px-6 py-4">
                                  <Badge variant={getStatusBadgeVariant(o.status)}>
                                    {o.status}
                                  </Badge>
                                </td>
                                <td className="px-6 py-4 text-text-sub">{parseDeliveryDetails(o.customer?.email)}</td>
                              </tr>
                            )
                          })
                        )}
                      </tbody>
                    </table>
                  </div>

                  {/* Pagination */}
                  {productionReport.orders?.meta && productionReport.orders.meta.last_page > 1 && (
                    <div className="flex items-center justify-between p-4 border-t border-border bg-stone-50 dark:bg-stone-900">
                      <span className="text-[10px] text-text-sub font-bold">
                        Página {productionReport.orders.meta.current_page} de {productionReport.orders.meta.last_page}
                      </span>
                      <div className="flex gap-2">
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={productionPage === 1}
                          onClick={() => setProductionPage(prev => Math.max(1, prev - 1))}
                        >
                          Anterior
                        </Button>
                        <Button
                          variant="secondary"
                          size="sm"
                          disabled={productionPage === productionReport.orders.meta.last_page}
                          onClick={() => setProductionPage(prev => prev + 1)}
                        >
                          Siguiente
                        </Button>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            </>
          ) : (
            <p className="text-center py-10 font-semibold text-text-sub">No hay registros de producción para mostrar.</p>
          )}
        </div>
      )}

    </div>
  )
}
