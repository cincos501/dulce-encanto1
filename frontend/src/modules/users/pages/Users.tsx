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
import { Badge, Button, Input, Label } from '@/design-system'
import usersService, { UserInput } from '@/shared/services/usersService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { User } from '@/shared/types'
import { FiEdit2, FiKey, FiTrash2 } from 'react-icons/fi'

const ROLES_LIST = [
  'Administrador',
  'Repostero',
  'Encargado de Operaciones y Suministros',
  'Encargado Comercial'
]

const createUserSchema = z.object({
  full_name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  email: z.string().min(1, 'El correo electrónico es requerido.').email('El correo electrónico no es válido.'),
  phone: z.string().max(20, 'El teléfono no puede superar los 20 caracteres.').optional().or(z.literal('')),
  role: z.string().min(1, 'Debe seleccionar un rol de sistema.'),
  password: z.string().min(6, 'La contraseña debe tener al menos 6 caracteres.'),
  is_active: z.boolean().default(true)
})

const editUserSchema = z.object({
  full_name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  email: z.string().min(1, 'El correo electrónico es requerido.').email('El correo electrónico no es válido.'),
  phone: z.string().max(20, 'El teléfono no puede superar los 20 caracteres.').optional().or(z.literal('')),
  role: z.string().min(1, 'Debe seleccionar un rol de sistema.'),
  is_active: z.boolean().default(true)
})

const resetPasswordSchema = z.object({
  password: z.string().min(6, 'La contraseña debe tener al menos 6 caracteres.')
})

type CreateUserFormInputs = z.infer<typeof createUserSchema>
type EditUserFormInputs = z.infer<typeof editUserSchema>
type ResetPasswordFormInputs = z.infer<typeof resetPasswordSchema>

export default function Users() {
  const queryClient = useQueryClient()
  const { user: currentUser, hasPermission } = useAuthorization()

  // Local state for pagination/filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10
  const [roleFilter, setRoleFilter] = useState<string>('')
  const [statusFilter, setStatusFilter] = useState<string>('')

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingUser, setEditingUser] = useState<User | null>(null)
  const [isDeleteOpen, setIsDeleteOpen] = useState<boolean>(false)
  const [userToDelete, setUserToDelete] = useState<User | null>(null)
  const [isResetOpen, setIsResetOpen] = useState<boolean>(false)
  const [userForReset, setUserForReset] = useState<User | null>(null)

  // React Hook Forms
  const createForm = useForm<CreateUserFormInputs>({
    resolver: zodResolver(createUserSchema),
    defaultValues: {
      full_name: '',
      email: '',
      phone: '',
      role: '',
      password: '',
      is_active: true
    }
  })

  const editForm = useForm<EditUserFormInputs>({
    resolver: zodResolver(editUserSchema),
    defaultValues: {
      full_name: '',
      email: '',
      phone: '',
      role: '',
      is_active: true
    }
  })

  const resetForm = useForm<ResetPasswordFormInputs>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: ''
    }
  })

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['users-list', page, search, roleFilter, statusFilter],
    queryFn: async () => {
      const activeParam = statusFilter === 'active' ? 'true' : statusFilter === 'inactive' ? 'false' : ''
      const response = await usersService.paginate(page, search, perPage, roleFilter, activeParam)
      return response.data
    }
  })

  const users = (queryData?.data as User[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Mutations
  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      const payload: UserInput = {
        full_name: data.full_name,
        email: data.email,
        phone: data.phone || null,
        role: data.role,
        is_active: data.is_active,
        ...(data.password ? { password: data.password } : {})
      }
      if (editingUser) {
        return usersService.update(editingUser.id, payload)
      } else {
        return usersService.create(payload)
      }
    },
    onSuccess: (response) => {
      toast.success(response.data.message || 'Usuario guardado con éxito.')
      queryClient.invalidateQueries({ queryKey: ['users-list'] })
      closeFormModal()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al guardar el usuario.'
      toast.error(message)
    }
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => usersService.delete(id),
    onSuccess: () => {
      toast.success('Usuario eliminado exitosamente.')
      queryClient.invalidateQueries({ queryKey: ['users-list'] })
      setIsDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se puede eliminar el usuario.'
      toast.error(message)
    }
  })

  const resetPasswordMutation = useMutation({
    mutationFn: ({ id, pass }: { id: number; pass: string }) => usersService.resetPassword(id, { password: pass }),
    onSuccess: () => {
      toast.success('Contraseña reestablecida con éxito.')
      setIsResetOpen(false)
      setUserForReset(null)
      resetForm.reset()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al actualizar contraseña.'
      toast.error(message)
    }
  })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => usersService.toggleActive(id),
    onSuccess: () => {
      toast.success('Estado del usuario modificado.')
      queryClient.invalidateQueries({ queryKey: ['users-list'] })
    },
    onError: () => toast.error('Error al cambiar estado.')
  })

  // Handlers
  const openCreateModal = () => {
    setEditingUser(null)
    createForm.reset({
      full_name: '',
      email: '',
      phone: '',
      role: '',
      is_active: true,
      password: ''
    })
    setIsFormOpen(true)
  }

  const openEditModal = (user: User) => {
    setEditingUser(user)
    editForm.reset({
      full_name: user.full_name,
      email: user.email,
      phone: user.phone || '',
      role: user.roles?.[0] || '',
      is_active: user.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingUser(null)
    createForm.reset()
    editForm.reset()
  }

  const handleRoleFilterChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setRoleFilter(e.target.value)
    setPage(1)
  }

  const handleStatusFilterChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setStatusFilter(e.target.value)
    setPage(1)
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: User, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Nombre Completo',
      cell: (item: User) => <span className="font-heading font-black text-sm text-primary">{item.full_name}</span>
    },
    {
      header: 'Email / Teléfono',
      cell: (item: User) => (
        <div className="flex flex-col gap-0.5 leading-tight">
          <span className="font-bold text-primary">{item.email}</span>
          <span className="text-[10px] text-text-sub font-semibold">{item.phone || 'Sin teléfono'}</span>
        </div>
      )
    },
    {
      header: 'Rol de Sistema',
      cell: (item: User) => (
        <Badge variant="info">{item.roles?.[0] || 'Personal'}</Badge>
      )
    },
    {
      header: 'Estado',
      cell: (item: User) => (
        <CrudStatusBadge
          isActive={item.is_active}
          disabled={currentUser?.id === item.id}
          onClick={() => toggleActiveMutation.mutate(item.id)}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: User) => (
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
          <Button
            variant="info"
            size="sm"
            onClick={() => {
              setUserForReset(item);
              setIsResetOpen(true);
            }}
            className="inline-flex items-center gap-1.5"
          >
            <FiKey className="text-xs" />
            <span>Clave</span>
          </Button>
          {hasPermission('users.manage') && currentUser?.id !== item.id && (
            <Button
              variant="ghost"
              size="sm"
              className="text-red-650 hover:bg-red-50 inline-flex items-center gap-1.5"
              onClick={() => {
                setUserToDelete(item);
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

  const createFields = [
    {
      name: 'full_name',
      label: 'Nombre Completo',
      type: 'text' as const,
      placeholder: 'Ej. Juan Pérez...',
      required: true
    },
    {
      name: 'email',
      label: 'Correo Electrónico',
      type: 'text' as const,
      placeholder: 'Ej. juan.perez@dulceencanto.com',
      required: true
    },
    {
      name: 'phone',
      label: 'Teléfono de Contacto (Opcional)',
      type: 'text' as const,
      placeholder: 'Ej. 099123456'
    },
    {
      name: 'role',
      label: 'Rol de Usuario',
      type: 'select' as const,
      required: true,
      options: ROLES_LIST.map(r => ({ value: r, label: r }))
    },
    {
      name: 'password',
      label: 'Contraseña de Acceso',
      type: 'password' as const,
      placeholder: 'Mínimo 6 caracteres',
      required: true
    },
    {
      name: 'is_active',
      label: 'Usuario Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el usuario podrá iniciar sesión en la plataforma.'
    }
  ]

  const editFields = [
    {
      name: 'full_name',
      label: 'Nombre Completo',
      type: 'text' as const,
      placeholder: 'Ej. Juan Pérez...',
      required: true
    },
    {
      name: 'email',
      label: 'Correo Electrónico',
      type: 'text' as const,
      placeholder: 'Ej. juan.perez@dulceencanto.com',
      required: true
    },
    {
      name: 'phone',
      label: 'Teléfono de Contacto (Opcional)',
      type: 'text' as const,
      placeholder: 'Ej. 099123456'
    },
    {
      name: 'role',
      label: 'Rol de Usuario',
      type: 'select' as const,
      required: true,
      options: ROLES_LIST.map(r => ({ value: r, label: r }))
    },
    {
      name: 'is_active',
      label: 'Usuario Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el usuario podrá iniciar sesión en la plataforma.'
    }
  ]

  return (
    <CrudPage
      title="Personal de Pastelería"
      subtitle="Administra las cuentas de accesos, roles and perfiles del personal del local."
      createLabel="Nuevo Integrante"
      createPermission="users.manage"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre, email..."
      extraActions={
        <>
          <select
            value={roleFilter}
            onChange={handleRoleFilterChange}
            className="px-4 py-2.5 rounded-lg border border-border focus:border-primary focus:ring-1 focus:ring-stone-200 outline-none text-xs text-text-main bg-surface"
          >
            <option value="">-- Todos los roles --</option>
            {ROLES_LIST.map(r => (
              <option key={r} value={r}>{r}</option>
            ))}
          </select>
          <select
            value={statusFilter}
            onChange={handleStatusFilterChange}
            className="px-4 py-2.5 rounded-lg border border-border focus:border-primary focus:ring-1 focus:ring-stone-200 outline-none text-xs text-text-main bg-surface"
          >
            <option value="">-- Todos los estados --</option>
            <option value="active">Activos</option>
            <option value="inactive">Inactivos</option>
          </select>
        </>
      }
    >
      <CrudTable
        data={users}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="integrantes"
      />

      {/* CREATE & EDIT FORM DIALOG */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingUser ? 'Editar Integrante' : 'Nuevo Integrante'}
        >
          {editingUser ? (
            <CrudForm
              fields={editFields}
              form={editForm}
              onSubmit={(data) => saveMutation.mutate(data)}
              onCancel={closeFormModal}
              isPending={saveMutation.isPending}
            />
          ) : (
            <CrudForm
              fields={createFields}
              form={createForm}
              onSubmit={(data) => saveMutation.mutate(data)}
              onCancel={closeFormModal}
              isPending={saveMutation.isPending}
            />
          )}
        </CrudModal>
      )}

      {/* PASSWORD RESET DIALOG */}
      {isResetOpen && (
        <CrudModal
          isOpen={isResetOpen}
          onClose={() => setIsResetOpen(false)}
          title="Cambiar Contraseña"
        >
          <form 
            onSubmit={resetForm.handleSubmit((data) => {
              if (userForReset) {
                resetPasswordMutation.mutate({ id: userForReset.id, pass: data.password })
              }
            })} 
            className="space-y-5"
          >
            <div className="space-y-1.5">
              <Label htmlFor="reset_pass">Nueva Contraseña</Label>
              <Input
                id="reset_pass"
                type="password"
                placeholder="Mínimo 6 caracteres"
                error={resetForm.formState.errors.password?.message}
                {...resetForm.register('password')}
              />
            </div>
            <div className="flex items-center justify-end gap-3 pt-4 border-t border-border/60">
              <Button
                type="button"
                variant="neutral"
                onClick={() => setIsResetOpen(false)}
              >
                Cancelar
              </Button>
              <Button
                type="submit"
                variant="primary"
                isLoading={resetPasswordMutation.isPending}
              >
                Guardar Contraseña
              </Button>
            </div>
          </form>
        </CrudModal>
      )}

      {/* DELETE CONFIRM DIALOG */}
      <CrudDeleteDialog
        isOpen={isDeleteOpen}
        onClose={() => setIsDeleteOpen(false)}
        onConfirm={() => userToDelete && deleteMutation.mutate(userToDelete.id)}
        isLoading={deleteMutation.isPending}
        title="¿Eliminar Integrante?"
        message={`Estás seguro de que deseas eliminar permanentemente la cuenta de "${userToDelete?.full_name}"? Esta acción no se puede deshacer.`}
      />
    </CrudPage>
  )
}
