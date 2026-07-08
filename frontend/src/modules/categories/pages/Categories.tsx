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
import categoriesService from '@/shared/services/categoriesService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Category } from '@/shared/types'
import { FiEdit2, FiTrash2 } from 'react-icons/fi'

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
  const [isDeleteOpen, setIsDeleteOpen] = useState<boolean>(false)
  const [categoryToDelete, setCategoryToDelete] = useState<Category | null>(null)

  // React Hook Form
  const form = useForm<CategoryFormInputs>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: '',
      description: '',
      is_active: true
    }
  })

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
      toast.success(response.data.message || 'Categoría guardada con éxito.')
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      closeFormModal()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al guardar la categoría.'
      toast.error(message)
    }
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => categoriesService.delete(id),
    onSuccess: () => {
      toast.success('Categoría eliminada con éxito.')
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      setIsDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se puede eliminar la categoría.'
      toast.error(message)
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => categoriesService.toggleActive(id),
    onSuccess: () => {
      toast.success('Estado de la categoría modificado.')
      queryClient.invalidateQueries({ queryKey: ['categories'] })
    },
    onError: () => toast.error('Error al modificar el estado.')
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
      cell: (item: Category) => (
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
          {hasPermission('categories.delete') && (
            <Button
              variant="ghost"
              size="sm"
              className="text-red-650 hover:bg-red-50 inline-flex items-center gap-1.5"
              onClick={() => {
                setCategoryToDelete(item);
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

      {/* DELETE CONFIRM DIALOG */}
      <CrudDeleteDialog
        isOpen={isDeleteOpen}
        onClose={() => setIsDeleteOpen(false)}
        onConfirm={() => categoryToDelete && deleteMutation.mutate(categoryToDelete.id)}
        isLoading={deleteMutation.isPending}
        title="¿Eliminar Categoría?"
        message={`Estás seguro de que deseas eliminar permanentemente la categoría "${categoryToDelete?.name}"? Esta acción no se puede deshacer.`}
      />
    </CrudPage>
  )
}
