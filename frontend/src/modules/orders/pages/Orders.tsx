import React, { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  CrudPage,
  CrudTable,
  CrudModal
} from '@/shared/components/crud'
import { Badge, Button, Divider, Select, Typography, Input, Textarea } from '@/design-system'
import ordersService, { Order } from '@/shared/services/ordersService'
import catalogService from '@/shared/services/catalogService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { FiEye, FiTrash2, FiPlus, FiCheck } from 'react-icons/fi'
import { toast } from 'sonner'
import { cn } from '@/shared/utils/cn'

export default function Orders() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local state
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modal detail state
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [isDetailOpen, setIsDetailOpen] = useState<boolean>(false)
  const [statusInput, setStatusInput] = useState<string>('')
  const [errorMsg, setErrorMsg] = useState<string | null>(null)

  // Create order states
  const [isCreateOpen, setIsCreateOpen] = useState<boolean>(false)
  const [customerType, setCustomerType] = useState<'existing' | 'new'>('new')
  const [selectedCustomerId, setSelectedCustomerId] = useState<string>('')
  const [newCustomerName, setNewCustomerName] = useState<string>('')
  const [newCustomerPhone, setNewCustomerPhone] = useState<string>('')
  const [orderItems, setOrderItems] = useState<any[]>([])

  // Item additions states
  const [selectedProductId, setSelectedProductId] = useState<string>('')
  const [selectedVariantId, setSelectedVariantId] = useState<string>('')
  const [selectedExtras, setSelectedExtras] = useState<any[]>([])
  const [itemQuantity, setItemQuantity] = useState<number>(1)

  // Delivery details states
  const [deliveryType, setDeliveryType] = useState<'Retiro en tienda' | 'Delivery'>('Retiro en tienda')
  const [address, setAddress] = useState<string>('')
  const [deliveryDate, setDeliveryDate] = useState<string>(new Date().toISOString().split('T')[0])
  const [deliveryTime, setDeliveryTime] = useState<string>('12:00')
  const [observations, setObservations] = useState<string>('')
  const [isCreatingOrder, setIsCreatingOrder] = useState<boolean>(false)
  const [createOrderError, setCreateOrderError] = useState<string | null>(null)

  // Query: Get active catalog products
  const { data: catalogProducts } = useQuery({
    queryKey: ['admin-catalog-products'],
    queryFn: async () => {
      const response = await catalogService.getCatalog()
      return response.data?.data || []
    },
    enabled: isCreateOpen
  })

  // Query: Get customers list
  const { data: customersList } = useQuery({
    queryKey: ['admin-customers-list'],
    queryFn: async () => {
      const response = await ordersService.getCustomers()
      return response.data?.data || []
    },
    enabled: isCreateOpen
  })

  // Query: Get product details for selection
  const { data: selectedProductDetail } = useQuery({
    queryKey: ['admin-product-detail', selectedProductId],
    queryFn: async () => {
      if (!selectedProductId) return null
      const response = await catalogService.getProductDetail(Number(selectedProductId))
      return response.data?.data || null
    },
    enabled: !!selectedProductId
  })

  useEffect(() => {
    if (selectedProductDetail && selectedProductDetail.variants.length > 0) {
      setSelectedVariantId(String(selectedProductDetail.variants[0].id))
    } else {
      setSelectedVariantId('')
    }
    setSelectedExtras([])
  }, [selectedProductDetail])

  // Query: Paginate Orders
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['orders', page, search],
    queryFn: async () => {
      const response = await ordersService.paginate(page, search, perPage)
      return response.data
    }
  })

  const orders = (queryData?.data as Order[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutation: Update Status
  const updateStatusMutation = useMutation({
    mutationFn: async () => {
      if (!selectedOrder) return
      return ordersService.updateStatus(selectedOrder.id, statusInput)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      toast.success('Estado del pedido actualizado con éxito.')
      closeDetailModal()
    },
    onError: (err: any) => {
      if (err.response?.status === 422) {
        // Validation error (like stock insufficient)
        const errors = err.response?.data?.errors || {}
        const firstErr = Object.values(errors)[0] as string[]
        setErrorMsg(firstErr ? firstErr[0] : 'Error de validación al iniciar la producción.')
      } else {
        const msg = err.response?.data?.message || 'Error al actualizar el estado.'
        setErrorMsg(msg)
      }
    }
  })

  // Handlers
  const openDetailModal = (order: Order) => {
    setSelectedOrder(order)
    setStatusInput(order.status)
    setErrorMsg(null)
    setIsDetailOpen(true)
  }
  const closeDetailModal = () => {
    setIsDetailOpen(false)
    setSelectedOrder(null)
    setStatusInput('')
    setErrorMsg(null)
  }

  const openCreateOrderModal = () => {
    setOrderItems([])
    setNewCustomerName('')
    setNewCustomerPhone('')
    setSelectedCustomerId('')
    setAddress('')
    setObservations('')
    setDeliveryType('Retiro en tienda')
    setCreateOrderError(null)
    setIsCreateOpen(true)
  }

  const handleAddOrderItem = () => {
    if (!selectedProductId || !selectedVariantId) return

    const product = catalogProducts?.find((p: any) => p.id === Number(selectedProductId))
    const variant = selectedProductDetail?.variants?.find((v: any) => v.id === Number(selectedVariantId))

    if (!product || !variant) return

    const activePrice = variant.promo_price !== null && variant.promo_price !== undefined ? Number(variant.promo_price) : Number(variant.price)
    const extrasPrice = selectedExtras.reduce((sum: number, ext: any) => sum + Number(ext.price), 0)
    const unitPrice = activePrice + extrasPrice

    const newItem = {
      key: `${variant.id}_${selectedExtras.map((e: any) => e.id).sort().join('-')}`,
      product_variant_id: variant.id,
      product_name: product.name,
      variant_name: variant.name,
      quantity: itemQuantity,
      price: unitPrice,
      extras: selectedExtras
    }

    setOrderItems((prev) => {
      const idx = prev.findIndex((item) => item.key === newItem.key)
      if (idx > -1) {
        const updated = [...prev]
        updated[idx].quantity += newItem.quantity
        return updated
      }
      return [...prev, newItem]
    })

    setSelectedProductId('')
    setSelectedVariantId('')
    setSelectedExtras([])
    setItemQuantity(1)
  }

  const handleRemoveOrderItem = (key: string) => {
    setOrderItems((prev) => prev.filter((item) => item.key !== key))
  }

  const handleCreateOrderSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (orderItems.length === 0) {
      setCreateOrderError('Debe agregar al menos un producto al pedido.')
      return
    }

    let customer_name = newCustomerName
    let customer_phone = newCustomerPhone

    if (customerType === 'existing') {
      const customer = customersList?.find((c: any) => c.id === Number(selectedCustomerId))
      if (!customer) {
        setCreateOrderError('Debe seleccionar un cliente existente.')
        return
      }
      customer_name = customer.full_name
      customer_phone = customer.phone
    } else {
      if (!customer_name.trim() || !customer_phone.trim()) {
        setCreateOrderError('El nombre y teléfono del cliente son requeridos.')
        return
      }
    }

    if (deliveryType === 'Delivery' && !address.trim()) {
      setCreateOrderError('La dirección de despacho es requerida para Delivery.')
      return
    }

    setIsCreatingOrder(true)
    setCreateOrderError(null)

    try {
      const payload = {
        customer_name,
        customer_phone,
        delivery_type: deliveryType,
        address: deliveryType === 'Delivery' ? address : null,
        observations: observations || null,
        delivery_date: deliveryDate,
        delivery_time: deliveryTime,
        items: orderItems.map((item) => ({
          product_variant_id: item.product_variant_id,
          quantity: item.quantity,
          extras: item.extras.map((e: any) => e.id)
        }))
      }

      await ordersService.checkout(payload)
      toast.success('Pedido creado manualmente con éxito.')
      setIsCreateOpen(false)
      setOrderItems([])
      setNewCustomerName('')
      setNewCustomerPhone('')
      setSelectedCustomerId('')
      setAddress('')
      setObservations('')
      setDeliveryType('Retiro en tienda')
      queryClient.invalidateQueries({ queryKey: ['orders'] })
    } catch (err: any) {
      const msg = err.response?.data?.message || 'Error al registrar el pedido.'
      setCreateOrderError(msg)
    } finally {
      setIsCreatingOrder(false)
    }
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
          <span className="font-sans font-bold text-sm text-text-main">
            {item.customer.full_name}
          </span>
          <span className="font-sans text-[10px] text-text-sub font-semibold">
            {item.customer.phone}
          </span>
        </div>
      )
    },
    {
      header: 'Fecha de Entrega',
      cell: (item: Order) => {
        if (!item.delivery_date) return <span className="font-sans text-xs italic text-text-sub/60">Sin fecha</span>
        const dateObj = new Date(item.delivery_date)
        return (
          <span className="font-sans text-xs font-semibold text-text-main">
            {dateObj.toLocaleDateString('es-CL', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
          </span>
        )
      }
    },
    {
      header: 'Total Pedido',
      cell: (item: Order) => (
        <span className="font-sans font-bold text-sm text-primary">
          Bs. {Number(item.total).toFixed(2)}
        </span>
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
      cell: (item: Order) => (
        <Button
          variant="secondary"
          size="sm"
          onClick={() => openDetailModal(item)}
          className="inline-flex items-center gap-1.5"
        >
          <FiEye className="text-xs" />
          <span>Ver Detalle</span>
        </Button>
      )
    }
  ]

  const statusOptions = [
    { value: 'Pendiente', label: 'Pendiente' },
    { value: 'Confirmado', label: 'Confirmado' },
    { value: 'En preparación', label: 'En preparación (Inicio de Producción)' },
    { value: 'Listo', label: 'Listo para Entrega' },
    { value: 'Entregado', label: 'Entregado' },
    { value: 'Cancelado', label: 'Cancelado' }
  ]

  return (
    <CrudPage
      title="Gestión de Pedidos"
      subtitle="Visualiza los pedidos de clientes y gestiona el flujo de estados de producción y entrega."
      createPermission="orders.create"
      createLabel="Nuevo Pedido"
      onCreateClick={openCreateOrderModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por folio o cliente..."
    >
      <CrudTable
        data={orders}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="pedidos"
      />

      {/* ORDER DETAIL & STATUS UPDATE MODAL */}
      {isDetailOpen && selectedOrder && (
        <CrudModal
          isOpen={isDetailOpen}
          onClose={closeDetailModal}
          title={`Detalle de Pedido #${String(selectedOrder.id).padStart(6, '0')}`}
          maxWidthClassName="max-w-2xl"
        >
          <div className="space-y-6 font-sans">
            {/* Customer & Delivery metadata */}
            {(() => {
              const delivery = getCustomerDeliveryDetails(selectedOrder.customer.email)
              return (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg">
                  <div className="space-y-1">
                    <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Cliente</span>
                    <p className="text-sm font-bold text-text-main">{selectedOrder.customer.full_name}</p>
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
                  <div className="space-y-1 md:text-right">
                    <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Fecha de Entrega Requerida</span>
                    <p className="text-sm font-bold text-primary">
                      {selectedOrder.delivery_date
                        ? new Date(selectedOrder.delivery_date).toLocaleString('es-CL', { dateStyle: 'medium', timeStyle: 'short' })
                        : 'No especificada'}
                    </p>
                    <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block pt-2">Fecha de Registro</span>
                    <p className="text-xs font-semibold text-text-sub">
                      {new Date(selectedOrder.created_at).toLocaleString('es-CL', { dateStyle: 'short', timeStyle: 'short' })}
                    </p>
                  </div>
                </div>
              )
            })()}

            {/* Order Items Table */}
            <div>
              <Typography variant="h4" className="font-heading font-black text-sm text-text-main mb-2">Artículos Solicitados</Typography>
              <div className="border border-border rounded-lg overflow-hidden bg-background">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-stone-50/80 dark:bg-stone-900/80 border-b border-border text-[10px] font-bold text-text-sub uppercase tracking-wider">
                      <th className="p-3">Producto / Presentación</th>
                      <th className="p-3 text-right">Cant.</th>
                      <th className="p-3 text-right">Precio Unit.</th>
                      <th className="p-3 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border text-xs">
                    {selectedOrder.items && selectedOrder.items.length > 0 ? (
                      selectedOrder.items.map((item) => (
                        <tr key={item.id} className="hover:bg-stone-50/50 dark:hover:bg-stone-850/50 transition-colors">
                          <td className="p-3">
                            <div className="font-bold text-text-main">{item.product_name}</div>
                            <div className="text-[10px] font-semibold text-text-sub italic">{item.presentation_name} (SKU: {item.sku})</div>
                          </td>
                          <td className="p-3 text-right font-semibold text-text-main">{item.quantity}</td>
                          <td className="p-3 text-right font-semibold text-text-sub">Bs. {Number(item.price).toFixed(2)}</td>
                          <td className="p-3 text-right font-bold text-primary">Bs. {Number(item.total).toFixed(2)}</td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan={4} className="p-4 text-center text-text-sub/50 italic font-semibold">Sin detalles del pedido</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
              <div className="flex justify-end pt-3">
                <div className="text-right">
                  <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Total General</span>
                  <span className="font-heading font-black text-lg text-primary">Bs. {Number(selectedOrder.total).toFixed(2)}</span>
                </div>
              </div>
            </div>

            <Divider />

            {/* Status flow configuration */}
            <div className="space-y-3 bg-amber-50/30 dark:bg-amber-950/10 border border-amber-250 p-4 rounded-lg">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="space-y-1">
                  <Typography variant="h4" className="font-heading font-black text-sm text-text-main">Estado del Pedido</Typography>
                  <p className="text-text-sub text-[10px]">Al cambiar a &apos;En preparación&apos;, se deducirán automáticamente los insumos de la receta de cada producto.</p>
                </div>

                {hasPermission('orders.update') ? (
                  <div className="w-full md:w-60">
                    <Select
                      options={statusOptions}
                      value={statusInput}
                      onChange={(e) => {
                        setStatusInput(e.target.value)
                        setErrorMsg(null)
                      }}
                      disabled={updateStatusMutation.isPending}
                    />
                  </div>
                ) : (
                  <Badge variant={getStatusBadgeVariant(selectedOrder.status)}>
                    {selectedOrder.status}
                  </Badge>
                )}
              </div>

              {/* Show inline error banner for insufficient stock or other validation problems */}
              {errorMsg && (
                <div className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/20 p-3 rounded-lg border border-red-200 animate-fade-in pl-4 relative">
                  <p>{errorMsg}</p>
                </div>
              )}
            </div>

            <Divider />

            {/* Modal Actions */}
            <div className="flex justify-end gap-3">
              <Button variant="secondary" onClick={closeDetailModal}>
                Cerrar
              </Button>
              {hasPermission('orders.update') && selectedOrder.status !== statusInput && (
                <Button
                  onClick={() => updateStatusMutation.mutate()}
                  disabled={updateStatusMutation.isPending}
                  className="font-bold tracking-wider"
                >
                  {updateStatusMutation.isPending ? 'Guardando...' : 'Guardar Estado'}
                </Button>
              )}
            </div>
          </div>
        </CrudModal>
      )}

      {isCreateOpen && (
        <CrudModal
          isOpen={isCreateOpen}
          onClose={() => setIsCreateOpen(false)}
          title="Registrar Nuevo Pedido"
          maxWidthClassName="max-w-4xl"
        >
          <form onSubmit={handleCreateOrderSubmit} className="space-y-6 font-sans text-text-main">
            {/* SECCIÓN 1: DATOS DEL CLIENTE */}
            <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg space-y-4">
              <Typography variant="h4" className="font-heading font-black text-sm text-primary uppercase tracking-wider">
                1. Información del Cliente
              </Typography>
              <div className="flex gap-4">
                <label className="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                  <input
                    type="radio"
                    name="custType"
                    checked={customerType === 'new'}
                    onChange={() => setCustomerType('new')}
                    className="accent-primary"
                  />
                  Registrar Nuevo Cliente
                </label>
                <label className="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                  <input
                    type="radio"
                    name="custType"
                    checked={customerType === 'existing'}
                    onChange={() => setCustomerType('existing')}
                    className="accent-primary"
                  />
                  Seleccionar Cliente Existente
                </label>
              </div>

              {customerType === 'existing' ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <Typography variant="label" className="text-[10px] uppercase tracking-widest font-bold block mb-1.5">Seleccionar Cliente</Typography>
                    <Select
                      options={[
                        { value: '', label: 'Seleccionar...' },
                        ...(customersList || []).map((c: any) => ({ value: String(c.id), label: `${c.full_name} (${c.phone})` }))
                      ]}
                      value={selectedCustomerId}
                      onChange={(e) => setSelectedCustomerId(e.target.value)}
                    />
                  </div>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Input
                    label="Nombre Completo"
                    placeholder="Ej. Juan Pérez"
                    value={newCustomerName}
                    onChange={(e) => setNewCustomerName(e.target.value)}
                    required
                  />
                  <Input
                    label="Teléfono de Contacto"
                    placeholder="Ej. +56912345678"
                    value={newCustomerPhone}
                    onChange={(e) => setNewCustomerPhone(e.target.value)}
                    required
                  />
                </div>
              )}
            </div>

            {/* SECCIÓN 2: PRODUCTOS Y PRESENTACIONES */}
            <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg space-y-4">
              <Typography variant="h4" className="font-heading font-black text-sm text-primary uppercase tracking-wider">
                2. Agregar Productos al Pedido
              </Typography>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-end bg-background border border-border p-4 rounded-lg">
                <div className="space-y-1">
                  <Typography variant="label" className="text-[10px] uppercase tracking-widest font-bold block mb-1.5">Seleccione un Producto</Typography>
                  <Select
                    options={[
                      { value: '', label: 'Seleccionar...' },
                      ...(catalogProducts || []).map((p: any) => ({ value: String(p.id), label: p.name }))
                    ]}
                    value={selectedProductId}
                    onChange={(e) => setSelectedProductId(e.target.value)}
                  />
                </div>

                {selectedProductId && selectedProductDetail && (
                  <div className="space-y-1">
                    <Typography variant="label" className="text-[10px] uppercase tracking-widest font-bold block mb-1.5">Presentación / Tamaño</Typography>
                    <Select
                      options={[
                        { value: '', label: 'Seleccionar...' },
                        ...(selectedProductDetail.variants || []).map((v: any) => {
                          const price = v.promo_price !== null && v.promo_price !== undefined ? v.promo_price : v.price
                          const labelText = `${v.name} (Bs. ${Number(price).toFixed(2)})${v.serves_people ? ` - ${v.serves_people} pers.` : ''}`
                          return { value: String(v.id), label: labelText }
                        })
                      ]}
                      value={selectedVariantId}
                      onChange={(e) => setSelectedVariantId(e.target.value)}
                    />
                  </div>
                )}

                {selectedProductId && selectedProductDetail && selectedVariantId && (
                  (() => {
                    const variant = selectedProductDetail.variants.find((v: any) => v.id === Number(selectedVariantId))
                    if (!variant || !variant.extras || variant.extras.length === 0) return null
                    return (
                      <div className="md:col-span-2 space-y-2 pt-2 border-t border-border/60">
                        <Typography variant="label" className="text-[10px] uppercase tracking-widest font-bold block mb-1">Adicionales / Extras disponibles:</Typography>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                          {variant.extras.map((ext: any) => {
                            const isChecked = selectedExtras.some(e => e.id === ext.id)
                            return (
                              <label key={ext.id} className="flex items-center gap-2 p-2 bg-stone-50 border border-border rounded text-xs font-semibold cursor-pointer select-none">
                                <input
                                  type="checkbox"
                                  checked={isChecked}
                                  onChange={(e) => {
                                    if (e.target.checked) {
                                      setSelectedExtras(prev => [...prev, { id: ext.id, name: ext.name, price: ext.price }])
                                    } else {
                                      setSelectedExtras(prev => prev.filter(item => item.id !== ext.id))
                                    }
                                  }}
                                  className="accent-primary"
                                />
                                <div>
                                  <span className="text-text-main font-bold">{ext.name}</span>
                                  <span className="text-primary font-bold block mt-0.5">+Bs. {Number(ext.price).toFixed(2)}</span>
                                </div>
                              </label>
                            )
                          })}
                        </div>
                      </div>
                    )
                  })()
                )}

                {selectedProductId && selectedVariantId && (
                  <div className="flex gap-4 items-end md:col-span-2 justify-between pt-2 border-t border-border/60">
                    <div className="flex items-center gap-2">
                      <Typography variant="label" className="text-[10px] uppercase tracking-widest font-bold">Cantidad:</Typography>
                      <input
                        type="number"
                        min="1"
                        value={itemQuantity}
                        onChange={(e) => setItemQuantity(Math.max(1, Number(e.target.value)))}
                        className="w-16 p-1.5 border border-border rounded text-xs font-bold text-center bg-background"
                      />
                    </div>
                    <Button type="button" onClick={handleAddOrderItem} className="text-xs uppercase font-bold tracking-wider flex items-center gap-1.5">
                      <FiPlus className="text-sm" />
                      <span>Agregar Producto</span>
                    </Button>
                  </div>
                )}
              </div>

              {/* LISTA DE ITEMS AGREGADOS */}
              {orderItems.length > 0 ? (
                <div className="border border-border rounded-lg overflow-hidden bg-background">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="bg-stone-50 dark:bg-stone-900 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                        <th className="p-3">Descripción</th>
                        <th className="p-3 text-center">Cantidad</th>
                        <th className="p-3 text-right">Precio Unit.</th>
                        <th className="p-3 text-right">Subtotal</th>
                        <th className="p-3 text-center w-12">Eliminar</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border text-xs">
                      {orderItems.map((item) => {
                        const itemSubtotal = item.price * item.quantity
                        return (
                          <tr key={item.key} className="hover:bg-stone-50/50 transition-colors">
                            <td className="p-3">
                              <span className="font-bold text-text-main block">{item.product_name}</span>
                              <span className="text-[10px] text-text-sub italic font-semibold">{item.variant_name}</span>
                              {item.extras.length > 0 && (
                                <span className="text-[9px] text-text-sub/80 font-semibold block mt-0.5">
                                  + Extras: {item.extras.map((e: any) => e.name).join(', ')}
                                </span>
                              )}
                            </td>
                            <td className="p-3 text-center font-bold text-text-main">{item.quantity}</td>
                            <td className="p-3 text-right font-semibold text-text-sub">Bs. {Number(item.price).toFixed(2)}</td>
                            <td className="p-3 text-right font-bold text-primary">Bs. {Number(itemSubtotal).toFixed(2)}</td>
                            <td className="p-3 text-center">
                              <button
                                type="button"
                                onClick={() => handleRemoveOrderItem(item.key)}
                                className="p-1.5 text-text-sub/50 hover:text-red-650 transition-colors rounded-lg hover:bg-red-50 cursor-pointer block mx-auto animate-scale-up"
                              >
                                <FiTrash2 className="text-xs" />
                              </button>
                            </td>
                          </tr>
                        )
                      })}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="p-6 border border-dashed border-border rounded-lg text-center text-xs text-text-sub font-semibold italic bg-background/50">
                  No se han agregado productos a este pedido.
                </div>
              )}
            </div>

            {/* SECCIÓN 3: DETALLES DE ENTREGA */}
            <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg space-y-4">
              <Typography variant="h4" className="font-heading font-black text-sm text-primary uppercase tracking-wider">
                3. Detalles de la Entrega
              </Typography>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  label="Fecha de Entrega"
                  type="date"
                  min={new Date().toISOString().split('T')[0]}
                  value={deliveryDate}
                  onChange={(e) => setDeliveryDate(e.target.value)}
                  required
                />
                <Input
                  label="Hora de Entrega"
                  type="time"
                  value={deliveryTime}
                  onChange={(e) => setDeliveryTime(e.target.value)}
                  required
                />
              </div>

              <div className="space-y-2">
                <Typography variant="label" className="text-[10px] uppercase tracking-widest block font-bold">
                  Tipo de Entrega
                </Typography>
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setDeliveryType('Retiro en tienda')}
                    className={`flex-1 py-2.5 px-4 rounded-lg border font-bold text-xs text-center transition-all ${deliveryType === 'Retiro en tienda'
                        ? 'border-primary bg-secondary/15 ring-1 ring-primary text-primary'
                        : 'border-border hover:border-stone-400 bg-surface text-text-sub'
                      }`}
                  >
                    Retiro en Tienda
                  </button>
                  <button
                    type="button"
                    onClick={() => setDeliveryType('Delivery')}
                    className={`flex-1 py-2.5 px-4 rounded-lg border font-bold text-xs text-center transition-all ${deliveryType === 'Delivery'
                        ? 'border-primary bg-secondary/15 ring-1 ring-primary text-primary'
                        : 'border-border hover:border-stone-400 bg-surface text-text-sub'
                      }`}
                  >
                    Delivery / Despacho
                  </button>
                </div>
              </div>

              {deliveryType === 'Delivery' && (
                <Textarea
                  label="Dirección de Despacho"
                  placeholder="Ej. Calle Falsa 123, depto 204"
                  value={address}
                  onChange={(e) => setAddress(e.target.value)}
                  required
                />
              )}

              <Textarea
                label="Observaciones adicionales"
                placeholder="Ej. Escribir dedicatoria: 'Feliz Cumpleaños'"
                value={observations}
                onChange={(e) => setObservations(e.target.value)}
              />
            </div>

            {/* Error banner */}
            {createOrderError && (
              <div className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/20 p-3 rounded-lg border border-red-200">
                <p>{createOrderError}</p>
              </div>
            )}

            {/* Total & Actions */}
            <div className="flex items-center justify-between border-t border-border pt-4">
              <div>
                <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Total del Pedido</span>
                <span className="font-heading font-black text-xl text-primary">
                  Bs. {orderItems.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)}
                </span>
              </div>

              <div className="flex gap-3">
                <Button type="button" variant="secondary" onClick={() => setIsCreateOpen(false)} disabled={isCreatingOrder}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={isCreatingOrder} className="font-bold uppercase tracking-wider">
                  {isCreatingOrder ? 'Guardando...' : (
                    <span className="flex items-center gap-1.5 justify-center">
                      <FiCheck className="text-sm" />
                      <span>Crear Pedido</span>
                    </span>
                  )}
                </Button>
              </div>
            </div>
          </form>
        </CrudModal>
      )}
    </CrudPage>
  )
}
