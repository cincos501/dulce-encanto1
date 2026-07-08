import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { toast } from '@/design-system'
import { 
  CrudPage, 
  CrudTable, 
  CrudModal, 
  CrudForm, 
  CrudDeleteDialog, 
  CrudStatusBadge 
} from '@/shared/components/crud'
import { Badge, Button, Typography } from '@/design-system'
import promotionsService from '@/shared/services/promotionsService'
import productVariantsService from '@/shared/services/productVariantsService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Promotion, ProductVariant } from '@/shared/types'
import { formatToLocalInput, formatToBackendDate } from '@/shared/utils/dateFormatter'
import { FiEdit2, FiTrash2 } from 'react-icons/fi'

const promotionSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  description: z.string().max(500, 'La descripción no puede superar los 500 caracteres.').optional().or(z.literal('')),
  discount_type: z.enum(['percentage', 'fixed']),
  discount: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El descuento debe ser un número.' }).min(0.01, 'El descuento debe ser mayor a 0.')
  ),
  start_date: z.string().min(1, 'La fecha de inicio es requerida.'),
  end_date: z.string().min(1, 'La fecha de cierre es requerida.'),
  is_active: z.boolean().default(true)
}).refine(data => new Date(data.start_date) < new Date(data.end_date), {
  message: 'La fecha de cierre debe ser posterior a la fecha de inicio.',
  path: ['end_date']
})

type PromotionFormInputs = z.infer<typeof promotionSchema>

export default function Promotions() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local state for filters/pagination
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10
  const [selectedVariantIds, setSelectedVariantIds] = useState<number[]>([])

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingPromotion, setEditingPromotion] = useState<Promotion | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState<boolean>(false)
  const [promoToDelete, setPromoToDelete] = useState<Promotion | null>(null)

  // React Hook Form
  const form = useForm<PromotionFormInputs>({
    resolver: zodResolver(promotionSchema),
    defaultValues: {
      name: '',
      description: '',
      discount_type: 'percentage',
      discount: 0,
      start_date: '',
      end_date: '',
      is_active: true
    }
  })

  // Queries
  const { data: variantsData } = useQuery({
    queryKey: ['variants-list-promotions'],
    queryFn: async () => {
      const response = await productVariantsService.getAll()
      return response.data?.data || []
    }
  })

  const allVariants = (variantsData as ProductVariant[]) || []

  const { data: queryData, isLoading } = useQuery({
    queryKey: ['promotions', page, search],
    queryFn: async () => {
      const response = await promotionsService.paginate(page, search, perPage)
      return response.data
    }
  })

  const promotions = (queryData?.data as Promotion[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: PromotionFormInputs) => {
      const payload = {
        ...data,
        start_date: formatToBackendDate(data.start_date),
        end_date: formatToBackendDate(data.end_date),
        product_variant_ids: selectedVariantIds
      }
      if (editingPromotion) {
        return promotionsService.update(editingPromotion.id, payload)
      } else {
        return promotionsService.create(payload)
      }
    },
    onSuccess: (response) => {
      toast.success(response.data.message || 'Promoción guardada con éxito.')
      queryClient.invalidateQueries({ queryKey: ['promotions'] })
      closeFormModal()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al guardar la promoción.'
      toast.error(message)
    }
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => promotionsService.delete(id),
    onSuccess: () => {
      toast.success('Promoción eliminada con éxito.')
      queryClient.invalidateQueries({ queryKey: ['promotions'] })
      setIsDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se puede eliminar la promoción.'
      toast.error(message)
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => promotionsService.toggleActive(id),
    onSuccess: () => {
      toast.success('Estado de la promoción modificado.')
      queryClient.invalidateQueries({ queryKey: ['promotions'] })
    },
    onError: () => toast.error('Error al modificar el estado.')
  })

  // Handlers
  const openCreateModal = () => {
    setEditingPromotion(null)
    setSelectedVariantIds([])
    form.reset({
      name: '',
      description: '',
      discount_type: 'percentage',
      discount: 0,
      start_date: '',
      end_date: '',
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (promotion: Promotion) => {
    setEditingPromotion(promotion)
    setSelectedVariantIds((promotion.variants || []).map(v => v.id))
    form.reset({
      name: promotion.name,
      description: promotion.description || '',
      discount_type: promotion.discount_type,
      discount: Number(promotion.discount),
      start_date: formatToLocalInput(promotion.start_date),
      end_date: formatToLocalInput(promotion.end_date),
      is_active: promotion.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingPromotion(null)
    form.reset()
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Promotion, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Promotion) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
    },
    {
      header: 'Tipo de Descuento',
      cell: (item: Promotion) => (
        <Badge variant="info">
          {item.discount_type === 'percentage' ? 'Porcentaje (%)' : 'Monto Fijo ($)'}
        </Badge>
      )
    },
    {
      header: 'Descuento',
      cell: (item: Promotion) => (
        <span className="font-bold text-primary font-mono">
          {item.discount_type === 'percentage' ? `${Number(item.discount).toFixed(0)}%` : `$${Number(item.discount).toFixed(2)}`}
        </span>
      )
    },
    {
      header: 'Periodo de Vigencia',
      cell: (item: Promotion) => (
        <span className="text-xs text-text-sub font-semibold">
          Desde: {new Date(item.start_date).toLocaleDateString()} al {new Date(item.end_date).toLocaleDateString()}
        </span>
      )
    },
    {
      header: 'Estado',
      cell: (item: Promotion) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('promotions.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Promotion) => (
        <>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => openEditModal(item)}
            className="inline-flex items-center gap-1.5"
          >
            <FiEdit2 className="text-xs" />
            <span>Editar</span>
          </Button>
          {hasPermission('promotions.delete') && (
            <Button
              variant="ghost"
              size="sm"
              className="text-red-650 hover:bg-red-50 inline-flex items-center gap-1.5"
              onClick={() => {
                setPromoToDelete(item);
                setIsDeleteOpen(true);
              }}
            >
              <FiTrash2 className="text-xs" />
              <span>Eliminar</span>
            </Button>
          )}
        </>
      )
    }
  ]

  const fields = [
    {
      name: 'name',
      label: 'Nombre de la Promoción',
      type: 'text' as const,
      placeholder: 'Ej. Descuento de Fin de Semana, Especial Día de la Madre...',
      required: true
    },
    {
      name: 'discount_type',
      label: 'Tipo de Descuento',
      type: 'select' as const,
      required: true,
      options: [
        { value: 'percentage', label: 'Porcentaje (%)' },
        { value: 'fixed', label: 'Monto Fijo ($)' }
      ]
    },
    {
      name: 'discount',
      label: 'Descuento',
      type: 'number' as const,
      placeholder: '0.00',
      step: '0.01',
      required: true
    },
    {
      name: 'start_date',
      label: 'Fecha y Hora de Inicio',
      type: 'datetime-local' as const,
      required: true
    },
    {
      name: 'end_date',
      label: 'Fecha y Hora de Cierre',
      type: 'datetime-local' as const,
      required: true
    },
    {
      name: 'description',
      label: 'Descripción (Opcional)',
      type: 'textarea' as const,
      placeholder: 'Detalles sobre la promoción, condiciones de compra...'
    },
    {
      name: 'is_active',
      label: 'Promoción Activa',
      type: 'switch' as const,
      placeholder: 'Determina si la promoción se aplicará a los productos.'
    }
  ]

  return (
    <CrudPage
      title="Promociones y Descuentos"
      subtitle="Define las campañas de descuento temporales para incentivar las ventas."
      createLabel="Nueva Promoción"
      createPermission="promotions.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre o descripción..."
    >
      <CrudTable
        data={promotions}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="promociones"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingPromotion ? 'Editar Promoción' : 'Nueva Promoción'}
        >
          <CrudForm
            fields={fields}
            form={form}
            onSubmit={(data) => saveMutation.mutate(data)}
            onCancel={closeFormModal}
            isPending={saveMutation.isPending}
          >
            {/* VARIANT PICKER */}
            <div className="bg-surface border border-border/80 p-5 rounded-lg space-y-3">
              <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Presentaciones de Producto Asociadas</Typography>
              <p className="text-text-sub text-[10px]">Selecciona los tamaños y productos que tendrán este descuento aplicado.</p>
              
              <div className="max-h-48 overflow-y-auto divide-y divide-border border border-border rounded-lg bg-background/50">
                {allVariants.length === 0 ? (
                  <p className="p-4 text-center text-[10px] text-text-sub/50 italic font-semibold">Cargando presentaciones de productos...</p>
                ) : (
                  allVariants.map((variant) => (
                    <div key={variant.id} className="flex items-center gap-2 p-2.5 hover:bg-stone-100/50 transition-colors animate-fade-in">
                      <input
                        type="checkbox"
                        id={`variant-${variant.id}`}
                        checked={selectedVariantIds.includes(variant.id)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedVariantIds(prev => [...prev, variant.id])
                          } else {
                            setSelectedVariantIds(prev => prev.filter(id => id !== variant.id))
                          }
                        }}
                        className="w-3.5 h-3.5 text-primary border-border rounded focus:ring-stone-250/20"
                      />
                      <label htmlFor={`variant-${variant.id}`} className="text-[10px] font-bold text-text-main cursor-pointer select-none leading-none flex items-center justify-between w-full">
                        <span>
                          {variant.product?.name} — {variant.name}
                        </span>
                        <span className="text-text-sub font-mono font-bold">${Number(variant.base_price).toFixed(2)}</span>
                      </label>
                    </div>
                  ))
                )}
              </div>
            </div>
          </CrudForm>
        </CrudModal>
      )}

      {/* DELETE CONFIRM DIALOG */}
      <CrudDeleteDialog
        isOpen={isDeleteOpen}
        onClose={() => setIsDeleteOpen(false)}
        onConfirm={() => promoToDelete && deleteMutation.mutate(promoToDelete.id)}
        isLoading={deleteMutation.isPending}
        title="¿Eliminar Promoción?"
        message={`Estás seguro de que deseas eliminar permanentemente la promoción "${promoToDelete?.name}"? Esta acción no se puede deshacer.`}
      />
    </CrudPage>
  )
}
