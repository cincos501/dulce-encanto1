import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { 
  CrudPage, 
  CrudTable, 
  CrudModal 
} from '@/shared/components/crud'
import { Badge, Button, Divider, Typography, Card, CardContent } from '@/design-system'
import ordersService, { Order, OrderItem } from '@/shared/services/ordersService'
import suppliesService from '@/shared/services/suppliesService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Supply } from '@/shared/types'
import { FiPlay, FiCheck, FiX, FiInfo, FiTruck, FiClock } from 'react-icons/fi'
import { toast } from 'sonner'
import { cn } from '@/shared/utils/cn'

export default function Production() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local state
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const [statusFilter, setStatusFilter] = useState<string>('') // empty means 'All' or filtered locally
  const perPage = 10

  // Modal detail state
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [isDetailOpen, setIsDetailOpen] = useState<boolean>(false)
  const [errorMsg, setErrorMsg] = useState<string | null>(null)

  // Query: Paginate Orders
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['production-orders', page, search],
    queryFn: async () => {
      // Reuses the orders paginated endpoint
      const response = await ordersService.paginate(page, search, 50) // load a larger batch to filter locally
      return response.data
    }
  })

  // Query: Active Supplies (to check recipe stocks in real-time on frontend)
  const { data: suppliesData } = useQuery({
    queryKey: ['supplies-stock-check'],
    queryFn: async () => {
      const response = await suppliesService.getAll()
      return response.data?.data || []
    }
  })

  const allOrders = (queryData?.data as Order[]) || []
  const suppliesList = (suppliesData as Supply[]) || []

  // Filter orders by status if selected
  const filteredOrders = statusFilter 
    ? allOrders.filter(o => o.status === statusFilter)
    : allOrders

  // Total counts for dashboard cards
  const countPending = allOrders.filter(o => o.status === 'Pendiente').length
  const countConfirmed = allOrders.filter(o => o.status === 'Confirmado').length
  const countInPrep = allOrders.filter(o => o.status === 'En preparación').length
  const countReady = allOrders.filter(o => o.status === 'Listo').length

  // Mutation: Transition order status
  const transitionMutation = useMutation({
    mutationFn: async (targetStatus: string) => {
      if (!selectedOrder) return
      return ordersService.updateStatus(selectedOrder.id, targetStatus)
    },
    onSuccess: (_, targetStatus) => {
      queryClient.invalidateQueries({ queryKey: ['production-orders'] })
      queryClient.invalidateQueries({ queryKey: ['supplies-stock-check'] })
      toast.success(`Pedido cambiado a "${targetStatus}" con éxito.`)
      closeDetailModal()
    },
    onError: (err: any) => {
      if (err.response?.status === 422) {
        const errors = err.response?.data?.errors || {}
        const firstErr = Object.values(errors)[0] as string[]
        setErrorMsg(firstErr ? firstErr[0] : 'Error de validación al realizar la transición.')
      } else {
        const msg = err.response?.data?.message || 'Error al actualizar el estado.'
        setErrorMsg(msg)
      }
    }
  })

  // Handlers
  const openDetailModal = (order: Order) => {
    setSelectedOrder(order)
    setErrorMsg(null)
    setIsDetailOpen(true)
  }

  const closeDetailModal = () => {
    setIsDetailOpen(false)
    setSelectedOrder(null)
    setErrorMsg(null)
  }

  const getStatusBadgeVariant = (status: Order['status']) => {
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

  const getCustomerDeliveryDetails = (emailStr: string | null) => {
    if (!emailStr) return { type: 'Retiro en tienda', address: '', obs: '' }
    try {
      if (emailStr.trim().startsWith('{')) {
        const parsed = JSON.parse(emailStr)
        return {
          type: parsed.delivery_type || 'Retiro en tienda',
          address: parsed.address || '',
          obs: parsed.observations || ''
        }
      }
    } catch {
      // Treat as normal email
    }
    return { type: 'Retiro en tienda', address: '', obs: '' }
  }

  // Calculate recipe requirements and check stock availability for selected order
  const checkOrderRecipeStock = (order: Order) => {
    const requiredSupplies: Record<number, { name: string; required: number; available: number; unit: string }> = {}

    order.items?.forEach((item) => {
      // Find the variant details in the system (or read the recipes loaded ansiosamente in backend detail)
      // Since backend show endpoint returns with relation 'items.productVariant.recipes.supply', we can look at it!
      // Wait, let's look at item.id or load it.
      // Wait, the API returns full details in show() or inside paginated items if eager loaded.
      // Let's verify: we loaded 'items.productVariant.recipes.supply' in OrderRepository!
      // So selectedOrder has recipes inside items! Let's check how the objects are structured:
      // item has productVariant which has recipes!
      const variant = (item as any).productVariant
      if (!variant || !variant.recipes) return

      variant.recipes.forEach((recipeItem: any) => {
        const supply = recipeItem.supply
        if (!supply) return

        const supplyId = supply.id
        const needed = Number(recipeItem.quantity) * Number(item.quantity)

        // Find current live stock from suppliesList query (to get freshest value)
        const liveSupply = suppliesList.find(s => s.id === supplyId)
        const available = liveSupply ? Number(liveSupply.stock) : Number(supply.stock)

        if (requiredSupplies[supplyId]) {
          requiredSupplies[supplyId].required += needed
        } else {
          requiredSupplies[supplyId] = {
            name: supply.name,
            required: needed,
            available: available,
            unit: recipeItem.unit || supply.unit
          }
        }
      })
    })

    return Object.values(requiredSupplies)
  }

  // Columns definition
  const columns = [
    {
      header: 'Folio',
      cell: (item: Order) => (
        <span className="font-mono font-bold text-xs text-text-sub">
          #{String(item.id).padStart(6, '0')}
        </span>
      )
    },
    {
      header: 'Cliente',
      cell: (item: Order) => (
        <div className="flex flex-col">
          <span className="font-sans font-bold text-xs text-text-main">
            {item.customer.full_name}
          </span>
          <span className="font-sans text-[9px] text-text-sub font-semibold">
            {item.customer.phone}
          </span>
        </div>
      )
    },
    {
      header: 'Fecha Entrega',
      cell: (item: Order) => {
        if (!item.delivery_date) return <span className="font-sans text-xs italic text-text-sub/60">Sin fecha</span>
        const dateObj = new Date(item.delivery_date)
        const isToday = dateObj.toDateString() === new Date().toDateString()
        return (
          <span className={cn("font-sans text-xs font-semibold flex items-center gap-1", isToday ? "text-amber-600 dark:text-amber-400 font-bold" : "text-text-main")}>
            <FiClock className="text-[10px]" />
            {dateObj.toLocaleDateString('es-CL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
          </span>
        )
      }
    },
    {
      header: 'Productos Requeridos',
      cell: (item: Order) => (
        <div className="max-w-xs truncate text-[11px] font-semibold text-text-sub">
          {item.items?.map(i => `${i.quantity}x ${i.product_name} (${i.presentation_name})`).join(', ')}
        </div>
      )
    },
    {
      header: 'Estado',
      cell: (item: Order) => (
        <Badge variant={getStatusBadgeVariant(item.status)}>
          {item.status}
        </Badge>
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right',
      cell: (item: Order) => {
        let actionLabel = 'Ver Detalle'
        let variant: 'primary' | 'secondary' = 'secondary'

        if (item.status === 'Confirmado') {
          actionLabel = 'Iniciar Producción'
          variant = 'primary'
        } else if (item.status === 'En preparación') {
          actionLabel = 'Marcar Listo'
          variant = 'primary'
        }

        return (
          <Button
            variant={variant}
            size="sm"
            onClick={() => openDetailModal(item)}
            className="inline-flex items-center gap-1.5"
          >
            {item.status === 'Confirmado' && <FiPlay className="text-[10px]" />}
            {item.status === 'En preparación' && <FiCheck className="text-xs" />}
            <span>{actionLabel}</span>
          </Button>
        )
      }
    }
  ]

  const recipeCheckList = selectedOrder ? checkOrderRecipeStock(selectedOrder) : []
  const hasInsufficientIngredients = recipeCheckList.some(r => r.available < r.required)

  return (
    <CrudPage
      title="Gestión de Producción y Cocina"
      subtitle="Supervisa los pedidos confirmados, analiza la disponibilidad de ingredientes en tiempo real y controla la preparación."
      createPermission="orders.create"
      onCreateClick={undefined}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar pedido por folio o cliente..."
    >
      {/* Dashboard cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card 
          onClick={() => setStatusFilter('Pendiente')}
          className={cn("cursor-pointer border hover:border-amber-400 transition-all", statusFilter === 'Pendiente' ? "border-amber-400 bg-amber-500/5" : "border-border")}
        >
          <CardContent className="p-4 flex flex-col justify-between h-20">
            <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Pendientes</span>
            <span className="font-heading font-black text-2xl text-amber-600 dark:text-amber-400">{countPending}</span>
          </CardContent>
        </Card>

        <Card 
          onClick={() => setStatusFilter('Confirmado')}
          className={cn("cursor-pointer border hover:border-stone-400 transition-all", statusFilter === 'Confirmado' ? "border-stone-400 bg-stone-100/50 dark:bg-stone-900/50" : "border-border")}
        >
          <CardContent className="p-4 flex flex-col justify-between h-20">
            <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Confirmados</span>
            <span className="font-heading font-black text-2xl text-text-main">{countConfirmed}</span>
          </CardContent>
        </Card>

        <Card 
          onClick={() => setStatusFilter('En preparación')}
          className={cn("cursor-pointer border hover:border-blue-400 transition-all", statusFilter === 'En preparación' ? "border-blue-400 bg-blue-500/5" : "border-border")}
        >
          <CardContent className="p-4 flex flex-col justify-between h-20">
            <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">En Preparación</span>
            <span className="font-heading font-black text-2xl text-blue-600 dark:text-blue-400">{countInPrep}</span>
          </CardContent>
        </Card>

        <Card 
          onClick={() => setStatusFilter('Listo')}
          className={cn("cursor-pointer border hover:border-emerald-400 transition-all", statusFilter === 'Listo' ? "border-emerald-400 bg-emerald-500/5" : "border-border")}
        >
          <CardContent className="p-4 flex flex-col justify-between h-20">
            <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Listos</span>
            <span className="font-heading font-black text-2xl text-emerald-600 dark:text-emerald-400">{countReady}</span>
          </CardContent>
        </Card>
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 mb-4 bg-stone-100/80 dark:bg-stone-900/80 p-1 rounded-lg w-fit border border-border">
        <button
          onClick={() => setStatusFilter('')}
          className={cn("px-3 py-1.5 rounded-md text-xs font-semibold transition-all", !statusFilter ? "bg-surface text-primary shadow-sm font-bold" : "text-text-sub hover:text-text-main")}
        >
          Todos ({allOrders.length})
        </button>
        <button
          onClick={() => setStatusFilter('Pendiente')}
          className={cn("px-3 py-1.5 rounded-md text-xs font-semibold transition-all", statusFilter === 'Pendiente' ? "bg-surface text-primary shadow-sm font-bold" : "text-text-sub hover:text-text-main")}
        >
          Pendientes
        </button>
        <button
          onClick={() => setStatusFilter('Confirmado')}
          className={cn("px-3 py-1.5 rounded-md text-xs font-semibold transition-all", statusFilter === 'Confirmado' ? "bg-surface text-primary shadow-sm font-bold" : "text-text-sub hover:text-text-main")}
        >
          Confirmados
        </button>
        <button
          onClick={() => setStatusFilter('En preparación')}
          className={cn("px-3 py-1.5 rounded-md text-xs font-semibold transition-all", statusFilter === 'En preparación' ? "bg-surface text-primary shadow-sm font-bold" : "text-text-sub hover:text-text-main")}
        >
          En Preparación
        </button>
        <button
          onClick={() => setStatusFilter('Listo')}
          className={cn("px-3 py-1.5 rounded-md text-xs font-semibold transition-all", statusFilter === 'Listo' ? "bg-surface text-primary shadow-sm font-bold" : "text-text-sub hover:text-text-main")}
        >
          Listos
        </button>
      </div>

      <CrudTable
        data={filteredOrders}
        columns={columns}
        isLoading={isLoading}
        currentPage={page}
        lastPage={1} // filtered locally, paginated batch
        total={filteredOrders.length}
        onPageChange={setPage}
        label="pedidos de cocina"
      />

      {/* KITCHEN DETAIL MODAL */}
      {isDetailOpen && selectedOrder && (
        <CrudModal
          isOpen={isDetailOpen}
          onClose={closeDetailModal}
          title={`Producción de Pedido #${String(selectedOrder.id).padStart(6, '0')}`}
          maxWidthClassName="max-w-2xl"
        >
          <div className="space-y-6 font-sans">
            {/* Customer metadata summary */}
            {(() => {
              const delivery = getCustomerDeliveryDetails(selectedOrder.customer.email)
              return (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg">
                  <div className="space-y-1">
                    <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Cliente</span>
                    <h4 className="text-sm font-bold text-text-main">{selectedOrder.customer.full_name}</h4>
                    <p className="text-xs text-text-sub font-semibold">Tel: {selectedOrder.customer.phone}</p>
                    
                    <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block pt-2">Tipo de Entrega</span>
                    <Badge variant={delivery.type === 'Delivery' ? 'info' : 'neutral'}>
                      {delivery.type}
                    </Badge>
                    {delivery.type === 'Delivery' && delivery.address && (
                      <p className="text-xs text-text-sub font-semibold mt-1">
                        <strong className="text-text-main">Dirección:</strong> {delivery.address}
                      </p>
                    )}
                    {delivery.obs && (
                      <p className="text-xs text-text-sub font-semibold mt-1">
                        <strong className="text-text-main">Observaciones:</strong> {delivery.obs}
                      </p>
                    )}
                  </div>
                  <div className="text-right flex flex-col justify-between items-end">
                    <div>
                      <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Entrega Requerida</span>
                      <p className="text-sm font-bold text-primary">
                        {selectedOrder.delivery_date 
                          ? new Date(selectedOrder.delivery_date).toLocaleString('es-CL', { dateStyle: 'medium', timeStyle: 'short' })
                          : 'No especificada'}
                      </p>
                    </div>
                    <Badge variant={getStatusBadgeVariant(selectedOrder.status)} className="mt-2">
                      {selectedOrder.status}
                    </Badge>
                  </div>
                </div>
              )
            })()}

            {/* List of items & observations */}
            <div>
              <Typography variant="h4" className="font-heading font-black text-sm text-text-main mb-2">Productos Solicitados</Typography>
              <div className="space-y-3">
                {selectedOrder.items?.map((item) => (
                  <div key={item.id} className="border border-border rounded-lg p-3.5 bg-background">
                    <div className="flex items-start justify-between">
                      <div>
                        <span className="font-heading font-black text-sm text-primary">{item.quantity}x {item.product_name}</span>
                        <p className="text-[10px] text-text-sub font-semibold italic">{item.presentation_name} (SKU: {item.sku})</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Recipe stock checking (Only show check when Confirmado or Pendiente) */}
            {selectedOrder.status === 'Confirmado' && (
              <div>
                <Divider />
                <div className="flex items-center gap-2 mb-3">
                  <FiInfo className="text-primary text-xs" />
                  <Typography variant="h4" className="font-heading font-black text-sm text-text-main">Disponibilidad de Ingredientes</Typography>
                </div>
                
                <div className="border border-border rounded-lg overflow-hidden bg-background">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="bg-stone-50/80 dark:bg-stone-900/80 border-b border-border text-[10px] font-bold text-text-sub uppercase tracking-wider">
                        <th className="p-3">Ingrediente</th>
                        <th className="p-3 text-right">Requerido</th>
                        <th className="p-3 text-right">Disponible</th>
                        <th className="p-3 text-center">Estado</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border text-xs">
                      {recipeCheckList.length === 0 ? (
                        <tr>
                          <td colSpan={4} className="p-4 text-center text-text-sub/50 italic font-semibold">Este pedido no tiene recetas asociadas. No se requiere descuento de insumos.</td>
                        </tr>
                      ) : (
                        recipeCheckList.map((item, index) => {
                          const isSufficient = item.available >= item.required
                          return (
                            <tr key={index} className="hover:bg-stone-50/50 dark:hover:bg-stone-850/50 transition-colors">
                              <td className="p-3 font-bold text-text-main">{item.name}</td>
                              <td className="p-3 text-right font-semibold text-text-sub">{item.required.toFixed(4)} {item.unit}</td>
                              <td className="p-3 text-right font-semibold text-text-main">{item.available.toFixed(4)} {item.unit}</td>
                              <td className="p-3 text-center">
                                <span className={cn(
                                  "inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider",
                                  isSufficient 
                                    ? "bg-emerald-105 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400" 
                                    : "bg-red-105 text-red-800 dark:bg-red-950/20 dark:text-red-400"
                                )}>
                                  {isSufficient ? 'Disponible' : 'Insuficiente'}
                                </span>
                              </td>
                            </tr>
                          )
                        })
                      )}
                    </tbody>
                  </table>
                </div>

                {hasInsufficientIngredients && (
                  <p className="text-red-500 text-[10px] font-semibold bg-red-50 dark:bg-red-950/10 p-2.5 rounded-lg border border-red-200 mt-2.5 animate-fade-in pl-4">
                    Atención: No hay stock suficiente de todos los insumos para iniciar la producción.
                  </p>
                )}
              </div>
            )}

            {/* In Preparation recipe review (read-only ingredient review) */}
            {selectedOrder.status === 'En preparación' && (
              <div>
                <Divider />
                <div className="flex items-center gap-2 mb-2">
                  <FiPlay className="text-blue-500 text-xs animate-pulse" />
                  <Typography variant="h4" className="font-heading font-black text-sm text-text-main">Insumos en Procesamiento</Typography>
                </div>
                <p className="text-[10px] text-text-sub mb-3">La producción se encuentra en curso. Los insumos ya han sido descontados del inventario.</p>
                <div className="max-h-36 overflow-y-auto divide-y divide-border border border-border rounded-lg bg-stone-50/50 p-2">
                  {recipeCheckList.map((item, idx) => (
                    <div key={idx} className="flex justify-between items-center py-1.5 text-xs">
                      <span className="font-semibold text-text-main">{item.name}</span>
                      <span className="font-bold text-blue-600 dark:text-blue-400">-{item.required.toFixed(4)} {item.unit}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Error notifications */}
            {errorMsg && (
              <div className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/25 p-3 rounded-lg border border-red-200 animate-fade-in pl-4">
                <p>{errorMsg}</p>
              </div>
            )}

            <Divider />

            {/* Production Actions based on transitions */}
            <div className="flex flex-col md:flex-row md:justify-between gap-4 pt-1">
              {/* Left secondary actions */}
              <div className="flex gap-2">
                {hasPermission('orders.update') && selectedOrder.status === 'Pendiente' && (
                  <>
                    <Button 
                      variant="primary" 
                      onClick={() => transitionMutation.mutate('Confirmado')}
                      disabled={transitionMutation.isPending}
                      className="font-bold tracking-wider"
                    >
                      {transitionMutation.isPending ? 'Procesando...' : 'Confirmar Pedido'}
                    </Button>
                    <Button 
                      variant="secondary" 
                      onClick={() => transitionMutation.mutate('Cancelado')}
                      disabled={transitionMutation.isPending}
                      className="bg-red-50 hover:bg-red-100 text-red-650 hover:text-red-700 border-red-200 font-bold tracking-wider"
                    >
                      Cancelar Pedido
                    </Button>
                  </>
                )}

                {hasPermission('orders.update') && selectedOrder.status === 'Confirmado' && (
                  <>
                    <Button 
                      variant="primary" 
                      onClick={() => transitionMutation.mutate('En preparación')}
                      disabled={transitionMutation.isPending || hasInsufficientIngredients}
                      className={cn("font-bold tracking-wider gap-1.5", hasInsufficientIngredients && "opacity-50 cursor-not-allowed bg-stone-400 hover:bg-stone-400 border-stone-400 active:scale-100")}
                    >
                      <FiPlay className="text-xs" />
                      <span>{transitionMutation.isPending ? 'Iniciando...' : 'Iniciar Producción'}</span>
                    </Button>
                    <Button 
                      variant="secondary" 
                      onClick={() => transitionMutation.mutate('Cancelado')}
                      disabled={transitionMutation.isPending}
                      className="bg-red-50 hover:bg-red-100 text-red-650 hover:text-red-700 border-red-200 font-bold tracking-wider"
                    >
                      Cancelar Pedido
                    </Button>
                  </>
                )}

                {hasPermission('orders.update') && selectedOrder.status === 'En preparación' && (
                  <>
                    <Button 
                      variant="primary" 
                      onClick={() => transitionMutation.mutate('Listo')}
                      disabled={transitionMutation.isPending}
                      className="font-bold tracking-wider gap-1.5"
                    >
                      <FiCheck className="text-xs" />
                      <span>{transitionMutation.isPending ? 'Guardando...' : 'Listo para Entrega'}</span>
                    </Button>
                    <Button 
                      variant="secondary" 
                      onClick={() => transitionMutation.mutate('Cancelado')}
                      disabled={transitionMutation.isPending}
                      className="bg-red-50 hover:bg-red-100 text-red-650 hover:text-red-700 border-red-200 font-bold tracking-wider"
                    >
                      Cancelar Pedido
                    </Button>
                  </>
                )}

                {hasPermission('orders.update') && selectedOrder.status === 'Listo' && (
                  <Button 
                    variant="primary" 
                    onClick={() => transitionMutation.mutate('Entregado')}
                    disabled={transitionMutation.isPending}
                    className="font-bold tracking-wider gap-1.5"
                  >
                    <FiTruck className="text-xs" />
                    <span>{transitionMutation.isPending ? 'Guardando...' : 'Registrar Entregado'}</span>
                  </Button>
                )}
              </div>

              {/* Close Button */}
              <div className="md:ml-auto">
                <Button variant="secondary" onClick={closeDetailModal}>
                  Cerrar
                </Button>
              </div>
            </div>
          </div>
        </CrudModal>
      )}
    </CrudPage>
  )
}
