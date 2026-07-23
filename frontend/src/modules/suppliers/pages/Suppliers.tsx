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
import suppliersService from '@/shared/services/suppliersService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Supplier } from '@/shared/types'
import { FiEdit2 } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { handleApiError } from '@/shared/utils/formErrors'

const supplierSchema = z.object({
  business_name: z.string()
    .min(2, 'La razón social debe tener al menos 2 caracteres.')
    .max(100, 'La razón social no puede superar los 100 caracteres.'),
  phone: z.string()
    .min(5, 'El teléfono de contacto debe tener al menos 5 caracteres.')
    .max(20, 'El teléfono no puede superar los 20 caracteres.'),
  email: z.string()
    .email('El correo debe ser una dirección de email válida.')
    .max(100, 'El correo no puede superar los 100 caracteres.')
    .optional()
    .or(z.literal('')),
  address: z.string()
    .max(255, 'La dirección no puede superar los 255 caracteres.')
    .optional()
    .or(z.literal('')),
  is_active: z.boolean().default(true)
})

type SupplierFormInputs = z.infer<typeof supplierSchema>

export default function Suppliers() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingSupplier, setEditingSupplier] = useState<Supplier | null>(null)

  // React Hook Form
  const form = useForm<SupplierFormInputs>({
    resolver: zodResolver(supplierSchema),
    defaultValues: {
      business_name: '',
      phone: '',
      email: '',
      address: '',
      is_active: true
    }
  })

  const businessName = form.watch('business_name')

  // Real-time uniqueness validation
  useEffect(() => {
    if (!businessName || businessName.trim().length < 2) {
      form.clearErrors('business_name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await suppliersService.getAll()
        const suppliers = response.data?.data || []
        const isDuplicate = suppliers.some(sup => 
          sup.business_name.toLowerCase() === businessName.trim().toLowerCase() && 
          sup.id !== editingSupplier?.id
        )
        if (isDuplicate) {
          form.setError('business_name', { type: 'manual', message: 'Ya existe un proveedor con esa razón social.' })
        } else {
          form.clearErrors('business_name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [businessName, editingSupplier, form])

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['suppliers', page, search],
    queryFn: async () => {
      const response = await suppliersService.paginate(page, search, perPage)
      return response.data
    }
  })

  const suppliers = (queryData?.data as Supplier[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: SupplierFormInputs) => {
      if (editingSupplier) {
        return suppliersService.update(editingSupplier.id, data)
      } else {
        return suppliersService.create(data)
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] })
      closeFormModal()
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al guardar el proveedor.')
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => suppliersService.toggleActive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] })
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al modificar el estado.')
    }
  })

  // Open forms
  const openCreateModal = () => {
    setEditingSupplier(null)
    form.reset({
      business_name: '',
      phone: '',
      email: '',
      address: '',
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (supplier: Supplier) => {
    setEditingSupplier(supplier)
    form.reset({
      business_name: supplier.business_name,
      phone: supplier.phone,
      email: supplier.email || '',
      address: supplier.address || '',
      is_active: supplier.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingSupplier(null)
    form.reset()
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Supplier, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Razón Social',
      cell: (item: Supplier) => <span className="font-heading font-black text-sm text-primary">{item.business_name}</span>
    },
    {
      header: 'Teléfono',
      cell: (item: Supplier) => <span className="font-sans text-xs font-semibold">{item.phone}</span>
    },
    {
      header: 'Correo',
      cell: (item: Supplier) => <span className="text-text-sub block text-xs truncate max-w-xs">{item.email || <span className="italic text-text-sub/40">Sin correo</span>}</span>
    },
    {
      header: 'Dirección',
      cell: (item: Supplier) => <span className="text-text-sub block text-xs truncate max-w-xs">{item.address || <span className="italic text-text-sub/40">Sin dirección</span>}</span>
    },
    {
      header: 'Estado',
      cell: (item: Supplier) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('suppliers.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Supplier) => {
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
      name: 'business_name',
      label: 'Razón Social / Nombre Comercial',
      type: 'text' as const,
      placeholder: 'Ej. Distribuidora Soprole, Molino Linderos...',
      required: true
    },
    {
      name: 'phone',
      label: 'Teléfono de Contacto',
      type: 'text' as const,
      placeholder: 'Ej. +56912345678',
      required: true
    },
    {
      name: 'email',
      label: 'Correo Electrónico (Opcional)',
      type: 'text' as const,
      placeholder: 'ejemplo@proveedor.com'
    },
    {
      name: 'address',
      label: 'Dirección Comercial (Opcional)',
      type: 'text' as const,
      placeholder: 'Ej. Av. Vitacura 4400, Santiago...'
    },
    {
      name: 'is_active',
      label: 'Proveedor Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el proveedor estará disponible para surtir insumos.'
    }
  ]

  return (
    <CrudPage
      title="Proveedores de Insumos"
      subtitle="Gestiona las distribuidoras e industrias que proveen las materias primas del local."
      createLabel="Nuevo Proveedor"
      createPermission="suppliers.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por razón social, teléfono o correo..."
    >
      <CrudTable
        data={suppliers}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="proveedores"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingSupplier ? 'Editar Proveedor' : 'Nuevo Proveedor'}
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
