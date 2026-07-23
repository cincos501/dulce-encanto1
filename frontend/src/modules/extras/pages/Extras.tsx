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
import { Badge, Button, Tooltip } from '@/design-system'
import extrasService from '@/shared/services/extrasService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Extra } from '@/shared/types'
import { FiEdit2 } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { handleApiError } from '@/shared/utils/formErrors'

const extraSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
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

  // React Hook Form
  const form = useForm<ExtraFormInputs>({
    resolver: zodResolver(extraSchema),
    defaultValues: {
      name: '',
      description: '',
      is_active: true
    }
  })

  const extraName = form.watch('name')

  // Real-time uniqueness validation
  useEffect(() => {
    if (!extraName || extraName.trim().length < 2) {
      form.clearErrors('name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await extrasService.getAll()
        const extras = response.data?.data || []
        const isDuplicate = extras.some(ext => 
          ext.name.toLowerCase() === extraName.trim().toLowerCase() && 
          ext.id !== editingExtra?.id
        )
        if (isDuplicate) {
          form.setError('name', { type: 'manual', message: 'Ya existe un adicional con ese nombre.' })
        } else {
          form.clearErrors('name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [extraName, editingExtra, form])

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
      queryClient.invalidateQueries({ queryKey: ['extras'] })
      closeFormModal()
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al guardar el adicional.')
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => extrasService.toggleActive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['extras'] })
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al modificar el estado.')
    }
  })

  // Handlers
  const openCreateModal = () => {
    setEditingExtra(null)
    form.reset({
      name: '',
      description: '',
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (extra: Extra) => {
    setEditingExtra(extra)
    form.reset({
      name: extra.name,
      description: extra.description || '',
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
      header: 'Número',
      cell: (_item: Extra, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Extra) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
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
      cell: (item: Extra) => {
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
      label: 'Nombre del Adicional',
      type: 'text' as const,
      placeholder: 'Ej. Velitas de cumpleaños, Caja de regalo...',
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
    </CrudPage>
  )
}
