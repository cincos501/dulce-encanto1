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
import { Badge, Button } from '@/design-system'
import extrasService from '@/shared/services/extrasService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Extra } from '@/shared/types'
import { FiEdit2, FiTrash2 } from 'react-icons/fi'

const extraSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  price: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El precio debe ser un número.' }).min(0.01, 'El precio debe ser mayor a 0.')
  ),
  description: z.string().max(255, 'La descripción no puede superar los 255 caracteres.').optional().or(z.literal('')),
  is_active: z.boolean().default(true)
})

type ExtraFormInputs = z.infer<typeof extraSchema>

export default function Extras() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local state for pagination/filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingExtra, setEditingExtra] = useState<Extra | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState<boolean>(false)
  const [extraToDelete, setExtraToDelete] = useState<Extra | null>(null)

  // React Hook Form
  const form = useForm<ExtraFormInputs>({
    resolver: zodResolver(extraSchema),
    defaultValues: {
      name: '',
      description: '',
      price: 0,
      is_active: true
    }
  })

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['extras', page, search],
    queryFn: async () => {
      const response = await extrasService.paginate(page, search, perPage)
      return response.data
    }
  })

  const extras = (queryData?.data as Extra[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: ExtraFormInputs) => {
      if (editingExtra) {
        return extrasService.update(editingExtra.id, data)
      } else {
        return extrasService.create(data)
      }
    },
    onSuccess: (response) => {
      toast.success(response.data.message || 'Adicional guardado con éxito.')
      queryClient.invalidateQueries({ queryKey: ['extras'] })
      closeFormModal()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al guardar el adicional.'
      toast.error(message)
    }
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => extrasService.delete(id),
    onSuccess: () => {
      toast.success('Adicional eliminado con éxito.')
      queryClient.invalidateQueries({ queryKey: ['extras'] })
      setIsDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se puede eliminar el adicional.'
      toast.error(message)
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => extrasService.toggleActive(id),
    onSuccess: () => {
      toast.success('Estado del extra modificado.')
      queryClient.invalidateQueries({ queryKey: ['extras'] })
    },
    onError: () => toast.error('Error al modificar el estado.')
  })

  // Handlers
  const openCreateModal = () => {
    setEditingExtra(null)
    form.reset({
      name: '',
      description: '',
      price: 0,
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (extra: Extra) => {
    setEditingExtra(extra)
    form.reset({
      name: extra.name,
      description: extra.description || '',
      price: Number(extra.price),
      is_active: extra.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingExtra(null)
    form.reset()
  }

  // Columns definition
  const columns = [
    {
      header: 'ID',
      cell: (item: Extra) => <span className="font-bold text-text-sub/60">#{item.id}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Extra) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
    },
    {
      header: 'Precio',
      cell: (item: Extra) => <span className="font-bold text-primary">${Number(item.price).toFixed(2)}</span>
    },
    {
      header: 'Descripción',
      cell: (item: Extra) => (
        <span className="text-text-sub block max-w-xs truncate">
          {item.description || <span className="italic text-text-sub/40">Sin descripción</span>}
        </span>
      )
    },
    {
      header: 'Estado',
      cell: (item: Extra) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('extras.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Extra) => (
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
          {hasPermission('extras.delete') && (
            <Button
              variant="ghost"
              size="sm"
              className="text-red-650 hover:bg-red-50 inline-flex items-center gap-1.5"
              onClick={() => {
                setExtraToDelete(item);
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
      label: 'Nombre del Adicional',
      type: 'text' as const,
      placeholder: 'Ej. Velitas de cumpleaños, Caja de regalo...',
      required: true
    },
    {
      name: 'price',
      label: 'Precio Extra ($)',
      type: 'number' as const,
      placeholder: '0.00',
      step: '0.01',
      required: true
    },
    {
      name: 'description',
      label: 'Descripción (Opcional)',
      type: 'textarea' as const,
      placeholder: 'Detalles sobre el adicional o presentación.'
    },
    {
      name: 'is_active',
      label: 'Adicional Activo',
      type: 'switch' as const,
      placeholder: 'Indica si estará disponible en el catálogo de compra.'
    }
  ]

  return (
    <CrudPage
      title="Adicionales y Coberturas"
      subtitle="Gestiona los ingredientes extras, coberturas y empaques opcionales que los clientes pueden sumar a su pedido."
      createLabel="Nuevo Adicional"
      createPermission="extras.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre o descripción..."
    >
      <CrudTable
        data={extras}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="adicionales"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingExtra ? 'Editar Adicional' : 'Nuevo Adicional'}
        >
          <CrudForm
            fields={fields}
            form={form}
            onSubmit={(data) => saveMutation.mutate(data)}
            onCancel={closeFormModal}
            isPending={saveMutation.isPending}
          />
        </CrudModal>
      )}

      {/* DELETE CONFIRM DIALOG */}
      <CrudDeleteDialog
        isOpen={isDeleteOpen}
        onClose={() => setIsDeleteOpen(false)}
        onConfirm={() => extraToDelete && deleteMutation.mutate(extraToDelete.id)}
        isLoading={deleteMutation.isPending}
        title="¿Eliminar Adicional?"
        message={`Estás seguro de que deseas eliminar permanentemente el adicional "${extraToDelete?.name}"? Esta acción no se puede deshacer.`}
      />
    </CrudPage>
  )
}
