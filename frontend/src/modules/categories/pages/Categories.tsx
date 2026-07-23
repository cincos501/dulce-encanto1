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
import categoriesService from '@/shared/services/categoriesService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Category } from '@/shared/types'
import { FiEdit2 } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { handleApiError } from '@/shared/utils/formErrors'

const categorySchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(50, 'El nombre no puede superar los 50 caracteres.'),
  description: z.string().max(255, 'La descripción no puede superar los 255 caracteres.').optional().or(z.literal('')),
  is_active: z.boolean().default(true)
})

type CategoryFormInputs = z.infer<typeof categorySchema>

export default function Categories() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingCategory, setEditingCategory] = useState<Category | null>(null)

  // React Hook Form
  const form = useForm<CategoryFormInputs>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: '',
      description: '',
      is_active: true
    }
  })

  const categoryName = form.watch('name')

  // Real-time uniqueness validation
  useEffect(() => {
    if (!categoryName || categoryName.trim().length < 2) {
      form.clearErrors('name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await categoriesService.getAll()
        const categories = response.data?.data || []
        const isDuplicate = categories.some(cat => 
          cat.name.toLowerCase() === categoryName.trim().toLowerCase() && 
          cat.id !== editingCategory?.id
        )
        if (isDuplicate) {
          form.setError('name', { type: 'manual', message: 'Ya existe una categoría con ese nombre.' })
        } else {
          form.clearErrors('name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [categoryName, editingCategory, form])

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['categories', page, search],
    queryFn: async () => {
      const response = await categoriesService.paginate(page, search, perPage)
      return response.data
    }
  })

  const categories = (queryData?.data as Category[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: CategoryFormInputs) => {
      if (editingCategory) {
        return categoriesService.update(editingCategory.id, data)
      } else {
        return categoriesService.create(data)
      }
    },
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      closeFormModal()
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al guardar la categoría.')
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => categoriesService.toggleActive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
    },
    onError: (err: any) => {
      handleApiError(err, form.setError, 'Error al modificar el estado.')
    }
  })

  // Open forms
  const openCreateModal = () => {
    setEditingCategory(null)
    form.reset({
      name: '',
      description: '',
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (category: Category) => {
    setEditingCategory(category)
    form.reset({
      name: category.name,
      description: category.description || '',
      is_active: category.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingCategory(null)
    form.reset()
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Category, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Category) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
    },
    {
      header: 'Descripción',
      cell: (item: Category) => (
        <span className="text-text-sub block max-w-md truncate">
          {item.description || <span className="italic text-text-sub/40">Sin descripción</span>}
        </span>
      )
    },
    {
      header: 'Estado',
      cell: (item: Category) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('categories.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Category) => {
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
      label: 'Nombre de la Categoría',
      type: 'text' as const,
      placeholder: 'Ej. Tortas, Muffin, Tartas...',
      required: true
    },
    {
      name: 'description',
      label: 'Descripción (Opcional)',
      type: 'textarea' as const,
      placeholder: 'Detalles sobre los dulces de esta categoría.'
    },
    {
      name: 'is_active',
      label: 'Categoría Activa',
      type: 'switch' as const,
      placeholder: 'Indica si se mostrará al público en el catálogo.'
    }
  ]

  return (
    <CrudPage
      title="Categorías de Repostería"
      subtitle="Administra las clasificaciones de pasteles, tortas y adicionales del negocio."
      createLabel="Nueva Categoría"
      createPermission="categories.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre o descripción..."
    >
      <CrudTable
        data={categories}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="categorías"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingCategory ? 'Editar Categoría' : 'Nueva Categoría'}
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
