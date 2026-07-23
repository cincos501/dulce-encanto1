import React, { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { 
  CrudPage, 
  CrudTable, 
  CrudModal, 
  CrudForm, 
  CrudStatusBadge 
} from '@/shared/components/crud'
import { Badge, Button, Tooltip, Typography, Input, Select, Divider } from '@/design-system'
import suppliesService from '@/shared/services/suppliesService'
import suppliersService from '@/shared/services/suppliersService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Supply, Supplier } from '@/shared/types'
import { FiEdit2, FiShoppingBag, FiPlus, FiTrash2 } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { handleApiError } from '@/shared/utils/formErrors'
import { toast } from 'sonner'

const supplySchema = z.object({
  name: z.string()
    .min(2, 'El nombre debe tener al menos 2 caracteres.')
    .max(100, 'El nombre no puede superar los 100 caracteres.'),
  unit: z.enum(['kg', 'g', 'L', 'ml', 'u', 'Caja', 'Bolsa', 'Paquete'], {
    required_error: 'La unidad de medida es requerida.'
  }),
  stock: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El stock debe ser un número.' }).min(0, 'El stock no puede ser negativo.')
  ),
  minimum_stock: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El stock mínimo debe ser un número.' }).min(0, 'El stock mínimo no puede ser negativo.')
  ),
  average_cost: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El costo promedio debe ser un número.' }).min(0, 'El costo no puede ser negativo.')
  ),
  is_active: z.boolean().default(true)
})

type SupplyFormInputs = z.infer<typeof supplySchema>

interface PurchaseItem {
  supply_id: number;
  quantity: number;
  purchase_price: number;
}

export default function Supplies() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingSupply, setEditingSupply] = useState<Supply | null>(null)
  
  // Pivot suppliers state
  const [selectedSuppliers, setSelectedSuppliers] = useState<{ supplier_id: number; purchase_price: number }[]>([])

  // Purchase state
  const [isPurchaseOpen, setIsPurchaseOpen] = useState<boolean>(false)
  const [purchaseSupplierId, setPurchaseSupplierId] = useState<number>(0)
  const [purchaseItems, setPurchaseItems] = useState<PurchaseItem[]>([])
  const [purchaseError, setPurchaseError] = useState<string | null>(null)

  // React Hook Form for supply
  const form = useForm<SupplyFormInputs>({
    resolver: zodResolver(supplySchema),
    defaultValues: {
      name: '',
      unit: '',
      stock: 0,
      minimum_stock: 0,
      average_cost: 0,
      is_active: true
    }
  })

  const supplyName = form.watch('name')

  // Real-time uniqueness validation
  useEffect(() => {
    if (!supplyName || supplyName.trim().length < 2) {
      form.clearErrors('name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await suppliesService.getAll()
        const supplies = response.data?.data || []
        const isDuplicate = supplies.some(sup => 
          sup.name.toLowerCase() === supplyName.trim().toLowerCase() && 
          sup.id !== editingSupply?.id
        )
        if (isDuplicate) {
          form.setError('name', { type: 'manual', message: 'Ya existe un insumo con ese nombre.' })
        } else {
          form.clearErrors('name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [supplyName, editingSupply, form])

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['supplies', page, search],
    queryFn: async () => {
      const response = await suppliesService.paginate(page, search, perPage)
      return response.data
    }
  })

  const { data: suppliersData } = useQuery({
    queryKey: ['active-suppliers'],
    queryFn: async () => {
      const response = await suppliersService.getAll(true) // only active
      return response.data?.data || []
    }
  })

  const supplies = (queryData?.data as Supply[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }
  const activeSuppliers = (suppliersData as Supplier[]) || []

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: SupplyFormInputs) => {
      const payload = {
        ...data,
        suppliers: selectedSuppliers
      }
      if (editingSupply) {
        return suppliesService.update(editingSupply.id, payload)
      } else {
        return suppliesService.create(payload)
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['supplies'] })
      closeFormModal()
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al guardar el insumo.')
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => suppliesService.toggleActive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['supplies'] })
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al modificar el estado.')
    }
  })

  const purchaseMutation = useMutation({
    mutationFn: async () => {
      if (!purchaseSupplierId) return
      return suppliesService.registerPurchase({
        supplier_id: purchaseSupplierId,
        items: purchaseItems
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['supplies'] })
      toast.success('Compra de insumos registrada con éxito.')
      closePurchaseModal()
    },
    onError: (err: any) => {
      if (err.response?.status === 422) {
        const errors = err.response?.data?.errors || {}
        const firstErr = Object.values(errors)[0] as string[]
        setPurchaseError(firstErr ? firstErr[0] : 'Error de validación al guardar la compra.')
      } else {
        const msg = err.response?.data?.message || 'Error al guardar la compra.'
        setPurchaseError(msg)
      }
    }
  })

  // Open forms
  const openCreateModal = () => {
    setEditingSupply(null)
    setSelectedSuppliers([])
    form.reset({
      name: '',
      unit: '',
      stock: 0,
      minimum_stock: 0,
      average_cost: 0,
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (supply: Supply) => {
    setEditingSupply(supply)
    form.reset({
      name: supply.name,
      unit: supply.unit,
      stock: supply.stock,
      minimum_stock: supply.minimum_stock,
      average_cost: supply.average_cost,
      is_active: supply.is_active
    })
    setSelectedSuppliers(
      supply.suppliers?.map(s => ({
        supplier_id: s.id,
        purchase_price: s.purchase_price
      })) || []
    )
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingSupply(null)
    setSelectedSuppliers([])
    form.reset()
  }

  // Purchase modal operations
  const openPurchaseModal = () => {
    setPurchaseSupplierId(activeSuppliers[0]?.id || 0)
    setPurchaseItems([])
    setPurchaseError(null)
    setIsPurchaseOpen(true)
  }

  const closePurchaseModal = () => {
    setIsPurchaseOpen(false)
    setPurchaseSupplierId(0)
    setPurchaseItems([])
    setPurchaseError(null)
  }

  const addPurchaseRow = () => {
    // Find first active supply not already in purchaseItems list
    const selectedIds = purchaseItems.map(item => item.supply_id)
    const availableSupply = supplies.find(s => s.is_active && !selectedIds.includes(s.id)) || supplies.find(s => s.is_active)

    if (!availableSupply) {
      toast.error('No hay insumos disponibles para agregar a la compra.')
      return
    }

    // Determine default purchase price
    const supplierRel = availableSupply.suppliers?.find(sup => sup.id === purchaseSupplierId)
    const defaultPrice = supplierRel ? supplierRel.purchase_price : (availableSupply.average_cost || 0)

    setPurchaseItems(prev => [
      ...prev,
      {
        supply_id: availableSupply.id,
        quantity: 1,
        purchase_price: defaultPrice
      }
    ])
  }

  const removePurchaseRow = (index: number) => {
    setPurchaseItems(prev => prev.filter((_, idx) => idx !== index))
  }

  const handlePurchaseSupplyChange = (index: number, supplyId: number) => {
    const supply = supplies.find(s => s.id === supplyId)
    if (!supply) return

    const supplierRel = supply.suppliers?.find(sup => sup.id === purchaseSupplierId)
    const defaultPrice = supplierRel ? supplierRel.purchase_price : (supply.average_cost || 0)

    setPurchaseItems(prev => prev.map((item, idx) => 
      idx === index 
        ? { ...item, supply_id: supplyId, purchase_price: defaultPrice } 
        : item
    ))
  }

  const handlePurchaseQuantityChange = (index: number, quantity: number) => {
    setPurchaseItems(prev => prev.map((item, idx) => 
      idx === index ? { ...item, quantity } : item
    ))
  }

  const handlePurchasePriceChange = (index: number, purchase_price: number) => {
    setPurchaseItems(prev => prev.map((item, idx) => 
      idx === index ? { ...item, purchase_price } : item
    ))
  }

  // Helper to toggle supplier selection (general crud modal)
  const handleSupplierCheckboxChange = (supplierId: number, checked: boolean) => {
    if (checked) {
      setSelectedSuppliers(prev => [...prev, { supplier_id: supplierId, purchase_price: 0 }])
    } else {
      setSelectedSuppliers(prev => prev.filter(s => s.supplier_id !== supplierId))
    }
  }

  // Helper to update price of selected supplier (general crud modal)
  const handleSupplierPriceChange = (supplierId: number, price: number) => {
    setSelectedSuppliers(prev => prev.map(s => 
      s.supplier_id === supplierId ? { ...s, purchase_price: price } : s
    ))
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Supply, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Supply) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
    },
    {
      header: 'Unidad',
      cell: (item: Supply) => <Badge variant="neutral">{item.unit}</Badge>
    },
    {
      header: 'Stock Actual',
      cell: (item: Supply) => {
        const isLowStock = item.stock <= item.minimum_stock
        return (
          <div className="flex items-center gap-1.5">
            <span className={cn("font-sans font-bold text-sm", isLowStock ? "text-red-650 dark:text-red-400" : "text-text-main")}>
              {Number(item.stock).toFixed(4)}
            </span>
            {isLowStock && (
              <span 
                className="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse" 
                title="Necesita reposición (Stock menor o igual al mínimo)"
              />
            )}
          </div>
        )
      }
    },
    {
      header: 'Stock Mínimo',
      cell: (item: Supply) => <span className="font-sans text-xs font-semibold text-text-sub">{Number(item.minimum_stock).toFixed(4)}</span>
    },
    {
      header: 'Costo Promedio',
      cell: (item: Supply) => <span className="font-sans text-xs font-bold text-emerald-600 dark:text-emerald-400">Bs. {Number(item.average_cost).toFixed(2)}</span>
    },
    {
      header: 'Proveedores',
      cell: (item: Supply) => (
        <div className="flex flex-wrap gap-1 max-w-xs">
          {item.suppliers && item.suppliers.length > 0 ? (
            item.suppliers.map(s => (
              <Badge key={s.id} variant="info" className="text-[10px] py-0.5">
                {s.business_name} (Bs. {s.purchase_price.toFixed(2)})
              </Badge>
            ))
          ) : (
            <span className="text-[10px] italic text-text-sub/55 font-semibold">Sin proveedores</span>
          )}
        </div>
      )
    },
    {
      header: 'Estado',
      cell: (item: Supply) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('supplies.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Supply) => {
        const isInactive = !item.is_active
        const editButton = (
          <Button
            variant="secondary"
            size="sm"
            onClick={isInactive ? undefined : () => openEditModal(item)}
            className={cn(
              "inline-flex items-center gap-1.5",
              isInactive && "bg-stone-200 hover:bg-stone-200 text-stone-400 cursor-not-allowed opacity-60 active:scale-100 dark:bg-stone-800 dark:text-stone-600"
            )}
          >
            <FiEdit2 className="text-xs" />
            <span>Editar</span>
          </Button>
        )

        return (
          <div className="inline-block">
            {isInactive ? (
              <Tooltip content="Debe activar nuevamente el registro para editarlo.">
                {editButton}
              </Tooltip>
            ) : (
              editButton
            )}
          </div>
        )
      }
    }
  ]

  const fields = [
    {
      name: 'name',
      label: 'Nombre del Insumo',
      type: 'text' as const,
      placeholder: 'Ej. Harina de Trigo, Mantequilla sin Sal...',
      required: true
    },
    {
      name: 'unit',
      label: 'Unidad de Medida',
      type: 'select' as const,
      required: true,
      options: [
        { value: 'kg', label: 'Kilogramo (kg)' },
        { value: 'g', label: 'Gramo (g)' },
        { value: 'L', label: 'Litro (L)' },
        { value: 'ml', label: 'Mililitro (ml)' },
        { value: 'u', label: 'Unidad (u)' },
        { value: 'Caja', label: 'Caja' },
        { value: 'Bolsa', label: 'Bolsa' },
        { value: 'Paquete', label: 'Paquete' }
      ]
    },
    {
      name: 'stock',
      label: 'Stock Inicial',
      type: 'number' as const,
      placeholder: '0.00',
      step: '0.0001',
      required: true
    },
    {
      name: 'minimum_stock',
      label: 'Stock Mínimo de Alerta',
      type: 'number' as const,
      placeholder: '0.00',
      step: '0.0001',
      required: true
    },
    {
      name: 'average_cost',
      label: 'Costo Promedio (Bs.)',
      type: 'number' as const,
      placeholder: '0.00',
      step: '0.01',
      required: true
    },
    {
      name: 'is_active',
      label: 'Insumo Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el insumo estará disponible para usarse en recetas.'
    }
  ]

  const activeSupplierOptions = activeSuppliers.map(s => ({
    value: s.id,
    label: s.business_name
  }))

  const activeSupplyOptions = supplies
    .filter(s => s.is_active)
    .map(s => ({
      value: s.id,
      label: `${s.name} (${s.unit})`
    }))

  return (
    <CrudPage
      title="Insumos y Materias Primas"
      subtitle="Gestiona el stock, la unidad de medida, el costo de producción y los proveedores asociados de cada ingrediente."
      createLabel="Nuevo Insumo"
      createPermission="supplies.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre o unidad..."
      extraActions={
        hasPermission('supplies.update') && (
          <Button
            variant="secondary"
            onClick={openPurchaseModal}
            className="flex items-center gap-1.5 text-xs font-semibold shrink-0"
          >
            <FiShoppingBag className="text-sm shrink-0" />
            <span>Registrar Compra</span>
          </Button>
        )
      }
    >
      <CrudTable
        data={supplies}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="insumos"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingSupply ? 'Editar Insumo' : 'Nuevo Insumo'}
        >
          <CrudForm
            fields={fields}
            form={form}
            onSubmit={(data) => saveMutation.mutate(data)}
            onCancel={closeFormModal}
            isPending={saveMutation.isPending}
          >
            {/* SUPPLIER PICKER */}
            <div className="bg-surface border border-border/80 p-5 rounded-lg space-y-3">
              <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Proveedores Asociados</Typography>
              <p className="text-text-sub text-[10px]">Selecciona los proveedores que surten este insumo y define sus respectivos precios de compra.</p>
              
              <div className="max-h-48 overflow-y-auto divide-y divide-border border border-border rounded-lg bg-background/50">
                {activeSuppliers.length === 0 ? (
                  <p className="p-4 text-center text-[10px] text-text-sub/50 italic font-semibold">Cargando proveedores activos...</p>
                ) : (
                  activeSuppliers.map((supplier) => {
                    const selectedRelation = selectedSuppliers.find(s => s.supplier_id === supplier.id)
                    const isChecked = !!selectedRelation

                    return (
                      <div key={supplier.id} className="flex items-center justify-between gap-4 p-2.5 hover:bg-stone-100/50 dark:hover:bg-stone-850/50 transition-colors animate-fade-in">
                        <div className="flex items-center gap-2">
                          <input
                            type="checkbox"
                            id={`supplier-${supplier.id}`}
                            checked={isChecked}
                            onChange={(e) => handleSupplierCheckboxChange(supplier.id, e.target.checked)}
                            className="rounded border-gray-300 text-amber-500 focus:ring-amber-500 h-3.5 w-3.5 cursor-pointer"
                          />
                          <label htmlFor={`supplier-${supplier.id}`} className="text-xs font-semibold text-text-main cursor-pointer select-none">
                            {supplier.business_name}
                          </label>
                        </div>

                        {isChecked && (
                          <div className="flex items-center gap-1">
                            <span className="text-[10px] font-bold text-text-sub">Bs.</span>
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={selectedRelation.purchase_price || ''}
                              onChange={(e) => handleSupplierPriceChange(supplier.id, Number(e.target.value))}
                              className="w-20 py-0.5 text-center text-xs font-semibold h-7"
                              required
                            />
                          </div>
                        )}
                      </div>
                    )
                  })
                )}
              </div>
            </div>
          </CrudForm>
        </CrudModal>
      )}

      {/* REGISTRAR COMPRA DIALOG */}
      {isPurchaseOpen && (
        <CrudModal
          isOpen={isPurchaseOpen}
          onClose={closePurchaseModal}
          title="Registrar Compra de Insumos"
          maxWidthClassName="max-w-3xl"
        >
          <div className="space-y-6 font-sans">
            <div className="space-y-4">
              {/* Supplier selection */}
              <div className="space-y-1">
                <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider">Proveedor</label>
                <Select
                  options={activeSupplierOptions}
                  value={purchaseSupplierId || ''}
                  onChange={(e) => {
                    setPurchaseSupplierId(Number(e.target.value))
                    setPurchaseItems([]) // Reset items on supplier change to avoid price mismatch
                    setPurchaseError(null)
                  }}
                  placeholder="Selecciona el proveedor..."
                />
              </div>

              <Divider />

              {/* Dynamic Purchase Rows */}
              <div className="flex items-center justify-between">
                <div>
                  <Typography variant="h4" className="font-heading font-black text-sm text-text-main">Detalle de Artículos Comprados</Typography>
                  <p className="text-text-sub text-[10px]">Añade los insumos adquiridos en esta transacción. Al registrarse se sumarán directamente al stock.</p>
                </div>
                {purchaseSupplierId > 0 && (
                  <Button 
                    size="sm" 
                    onClick={addPurchaseRow}
                    className="text-[10px] uppercase font-bold tracking-wider gap-1.5"
                  >
                    <FiPlus className="text-xs" />
                    Añadir Insumo
                  </Button>
                )}
              </div>

              {/* General errors message */}
              {purchaseError && (
                <p className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/20 p-2.5 rounded border border-red-200">
                  {purchaseError}
                </p>
              )}

              {/* Rows List */}
              <div className="space-y-3 max-h-80 overflow-y-auto pr-1">
                {purchaseItems.length === 0 ? (
                  <div className="text-center py-8 border border-dashed border-border rounded-lg bg-background/50">
                    <p className="text-xs text-text-sub/50 italic font-semibold">No se han añadido insumos a esta compra.</p>
                    {purchaseSupplierId > 0 && (
                      <Button size="sm" onClick={addPurchaseRow} className="mt-3 text-[10px] uppercase tracking-wider">
                        Añadir primer insumo
                      </Button>
                    )}
                  </div>
                ) : (
                  purchaseItems.map((item, index) => {
                    const supply = supplies.find(s => s.id === item.supply_id)
                    const unit = supply ? supply.unit : ''

                    return (
                      <div key={index} className="bg-surface border border-border/80 p-3.5 rounded-lg flex flex-col md:flex-row md:items-start gap-4 hover:shadow-sm transition-shadow animate-fade-in">
                        {/* Supply */}
                        <div className="flex-grow space-y-1">
                          <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Insumo</label>
                          <Select
                            options={activeSupplyOptions}
                            value={item.supply_id || ''}
                            onChange={(e) => handlePurchaseSupplyChange(index, Number(e.target.value))}
                            placeholder="Seleccionar..."
                          />
                        </div>

                        {/* Quantity */}
                        <div className="w-full md:w-32 space-y-1">
                          <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Cantidad</label>
                          <div className="relative">
                            <Input
                              type="number"
                              step="0.0001"
                              placeholder="0.00"
                              value={item.quantity || ''}
                              onChange={(e) => handlePurchaseQuantityChange(index, Number(e.target.value))}
                              className="pr-10 text-xs font-semibold"
                              required
                            />
                            <span className="absolute right-3 top-2.5 text-xs font-bold text-text-sub pointer-events-none select-none">
                              {unit}
                            </span>
                          </div>
                        </div>

                        {/* Purchase Price */}
                        <div className="w-full md:w-36 space-y-1">
                          <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Precio Unitario (Bs.)</label>
                          <div className="relative">
                            <Input
                              type="number"
                              step="0.01"
                              placeholder="0.00"
                              value={item.purchase_price || ''}
                              onChange={(e) => handlePurchasePriceChange(index, Number(e.target.value))}
                              className="pl-6 text-xs font-semibold"
                              required
                            />
                            <span className="absolute left-3 top-2.5 text-xs font-bold text-text-sub pointer-events-none select-none">
                              Bs.
                            </span>
                          </div>
                        </div>

                        {/* Delete Action */}
                        <div className="pt-6 shrink-0">
                          <Button
                            variant="secondary"
                            onClick={() => removePurchaseRow(index)}
                            className="bg-red-50 hover:bg-red-100 text-red-650 hover:text-red-700 p-2.5 rounded-lg border border-red-200 flex items-center justify-center cursor-pointer"
                            title="Quitar de la compra"
                          >
                            <FiTrash2 className="text-sm" />
                          </Button>
                        </div>
                      </div>
                    )
                  })
                )}
              </div>
            </div>

            <Divider />

            {/* Modal actions */}
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={closePurchaseModal}>
                Cancelar
              </Button>
              {purchaseItems.length > 0 && (
                <Button 
                  onClick={() => purchaseMutation.mutate()} 
                  disabled={purchaseMutation.isPending}
                  className="font-bold tracking-wider"
                >
                  {purchaseMutation.isPending ? 'Registrando...' : 'Registrar Compra'}
                </Button>
              )}
            </div>
          </div>
        </CrudModal>
      )}
    </CrudPage>
  )
}
