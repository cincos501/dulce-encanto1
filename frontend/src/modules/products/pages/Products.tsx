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
import { 
  Button, 
  Card, 
  CardContent,
  Tabs, 
  Divider, 
  ImageUploader, 
  ImagePreview, 
  Label,
  Input,
  Select,
  Textarea,
  Switch,
  Badge,
  Typography,
  Tooltip,
  toast
} from '@/design-system'
import productsService from '@/shared/services/productsService'
import categoriesService from '@/shared/services/categoriesService'
import productVariantsService from '@/shared/services/productVariantsService'
import productImagesService from '@/shared/services/productImagesService'
import extrasService from '@/shared/services/extrasService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Product, Category, ProductVariant, ProductImage, Extra } from '@/shared/types'
import { FiEdit2, FiPlus, FiInfo, FiImage, FiX } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { handleApiError } from '@/shared/utils/formErrors'

// Zod schemas
const productSchema = z.object({
  category_id: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ required_error: 'La categoría es requerida.' }).min(1, 'Categoría no válida.')
  ),
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  description: z.string().max(500, 'La descripción no puede superar los 500 caracteres.').optional().or(z.literal('')),
  is_active: z.boolean().default(true)
})

type ProductFormInputs = z.infer<typeof productSchema>

const variantSchema = z.object({
  name: z.string().min(2, 'La presentación debe tener al menos 2 caracteres.').max(100, 'El nombre no puede superar los 100 caracteres.'),
  price: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El precio debe ser un número.' }).min(0.01, 'El precio debe ser mayor a 0.')
  ),
  serves_people: z.preprocess(
    (val) => (val === '' || val === null ? undefined : Number(val)),
    z.number({ invalid_type_error: 'La cantidad de personas debe ser un número.' }).int().min(1, 'Debe ser para al menos 1 persona.').optional()
  ),
  is_active: z.boolean().default(true)
})

type VariantFormInputs = z.infer<typeof variantSchema>

export default function Products() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // General state
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Active product form/tab state
  const [isFormOpen, setIsFormOpen] = useState<boolean>(false)
  const [editingProduct, setEditingProduct] = useState<Product | null>(null)
  const [activeTab, setActiveTab] = useState<string>('general')

  // Sub-resource states (only active when editing)
  const [variantsList, setVariantsList] = useState<ProductVariant[]>([])
  const [isVariantModalOpen, setIsVariantModalOpen] = useState<boolean>(false)
  const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null)

  // Variant Image state
  const [variantImages, setVariantImages] = useState<ProductImage[]>([])
  const [isImageUploading, setIsImageUploading] = useState<boolean>(false)
  const [selectedExtrasWithPrices, setSelectedExtrasWithPrices] = useState<{ extra_id: number; price: number }[]>([])

  // React Hook Forms
  const productForm = useForm<ProductFormInputs>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      category_id: undefined,
      name: '',
      description: '',
      is_active: true
    }
  })

  const variantForm = useForm<VariantFormInputs>({
    resolver: zodResolver(variantSchema),
    defaultValues: {
      name: '',
      price: 0,
      serves_people: 1,
      is_active: true
    }
  })

  const productName = productForm.watch('name')

  // Real-time product name uniqueness validation
  useEffect(() => {
    if (!productName || productName.trim().length < 2) {
      productForm.clearErrors('name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await productsService.getAll()
        const productsList = response.data?.data || []
        const isDuplicate = productsList.some(p => 
          p.name.toLowerCase() === productName.trim().toLowerCase() && 
          p.id !== editingProduct?.id
        )
        if (isDuplicate) {
          productForm.setError('name', { type: 'manual', message: 'Ya existe un producto con ese nombre.' })
        } else {
          productForm.clearErrors('name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [productName, editingProduct, productForm])

  const variantName = variantForm.watch('name')

  // Real-time variant name uniqueness validation
  useEffect(() => {
    if (!variantName || variantName.trim().length < 2 || !editingProduct) {
      variantForm.clearErrors('name')
      return
    }

    const checkUniqueness = setTimeout(async () => {
      try {
        const response = await productVariantsService.getAll(editingProduct.id)
        const variants = response.data?.data || []
        const isDuplicate = variants.some(v => 
          v.name.toLowerCase() === variantName.trim().toLowerCase() && 
          v.id !== editingVariant?.id
        )
        if (isDuplicate) {
          variantForm.setError('name', { type: 'manual', message: 'Ya existe una presentación con ese nombre para este producto.' })
        } else {
          variantForm.clearErrors('name')
        }
      } catch {
        // Ignore API errors
      }
    }, 400)

    return () => clearTimeout(checkUniqueness)
  }, [variantName, editingVariant, editingProduct, variantForm])

  // Queries
  const { data: categoriesData } = useQuery({
    queryKey: ['categories-list-select-active'],
    queryFn: async () => {
      // Pass true for onlyActive
      const response = await categoriesService.getAll(true)
      return response.data?.data || []
    }
  })

  const categories = (categoriesData as Category[]) || []

  const { data: extrasData } = useQuery({
    queryKey: ['extras-list-product-form-active'],
    queryFn: async () => {
      // Pass true for onlyActive
      const response = await extrasService.getAll(true)
      return response.data?.data || []
    }
  })

  const extrasList = (extrasData as Extra[]) || []

  const { data: queryData, isLoading } = useQuery({
    queryKey: ['products', page, search],
    queryFn: async () => {
      const response = await productsService.paginate(page, search, perPage)
      return response.data
    }
  })

  const products = (queryData?.data as Product[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Load product variants & images when editing
  const loadSubResources = async (productId: number) => {
    try {
      const response = await productVariantsService.getAll(productId)
      const variants = response.data?.data || []
      setVariantsList(variants)
    } catch {
      toast.error('Error al cargar presentaciones del producto.')
    }
  }

  useEffect(() => {
    if (editingProduct) {
      loadSubResources(editingProduct.id)
    } else {
      setVariantsList([])
    }
  }, [editingProduct])

  // Mutation: Product Store/Update
  const saveProductMutation = useMutation({
    mutationFn: async (data: ProductFormInputs) => {
      if (editingProduct) {
        return productsService.update(editingProduct.id, data)
      } else {
        return productsService.create(data)
      }
    },
    onSuccess: (response) => {
      const savedProd = response.data.data
      queryClient.invalidateQueries({ queryKey: ['products'] })
      
      if (!editingProduct) {
        // Switch to editing mode to let the user add variants & photos immediately
        setEditingProduct(savedProd)
        setActiveTab('variants')
      } else {
        closeFormModal()
      }
    },
    onError: (err: any) => {
      handleApiError(err, productForm.setError, 'Error al guardar el producto.')
    }
  })

  // Mutation: Variant Store/Update
  const saveVariantMutation = useMutation({
    mutationFn: async (data: VariantFormInputs) => {
      if (!editingProduct) return
      const payload = {
        product_id: editingProduct.id,
        name: data.name,
        price: data.price,
        serves_people: data.serves_people,
        is_active: data.is_active,
        extras: selectedExtrasWithPrices
      }
      if (editingVariant) {
        return productVariantsService.update(editingVariant.id, payload)
      } else {
        return productVariantsService.create(payload)
      }
    },
    onSuccess: () => {
      if (editingProduct) loadSubResources(editingProduct.id)
      setIsVariantModalOpen(false)
      setEditingVariant(null)
      variantForm.reset()
    },
    onError: (err: any) => {
      handleApiError(err, variantForm.setError, 'Error al guardar la variante.')
    }
  })

  // Mutation: Toggle Active Product
  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => productsService.toggleActive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
    },
    onError: (err: any) => {
      toast.error('Error al actualizar el estado.')
    }
  })

  // Mutation: Toggle Active Variant
  const toggleActiveVariantMutation = useMutation({
    mutationFn: (id: number) => productVariantsService.toggleActive(id),
    onSuccess: () => {
      if (editingProduct) loadSubResources(editingProduct.id)
    },
    onError: (err: any) => {
      toast.error('Error al actualizar el estado de la variante.')
    }
  })

  // Handlers for Modals
  const openCreateModal = () => {
    setEditingProduct(null)
    setActiveTab('general')
    productForm.reset({
      category_id: undefined,
      name: '',
      description: '',
      is_active: true
    })
    setIsFormOpen(true)
  }

  const openEditModal = (product: Product) => {
    setEditingProduct(product)
    setActiveTab('general')
    productForm.reset({
      category_id: product.category_id,
      name: product.name,
      description: product.description || '',
      is_active: product.is_active
    })
    setIsFormOpen(true)
  }

  const closeFormModal = () => {
    setIsFormOpen(false)
    setEditingProduct(null)
    productForm.reset()
  }

  const openCreateVariantModal = () => {
    setEditingVariant(null)
    setSelectedExtrasWithPrices([])
    setVariantImages([])
    variantForm.reset({
      name: '',
      price: 0,
      serves_people: 1,
      is_active: true
    })
    setIsVariantModalOpen(true)
  }

  const openEditVariantModal = async (variant: ProductVariant) => {
    setEditingVariant(variant)
    setSelectedExtrasWithPrices((variant.extras || []).map(e => ({
      extra_id: e.id,
      price: e.price !== undefined && e.price !== null ? Number(e.price) : 0
    })))
    
    // Load variant images
    try {
      const res = await productImagesService.getByVariantId(variant.id)
      setVariantImages(res.data?.data || [])
    } catch {
      setVariantImages([])
    }

    variantForm.reset({
      name: variant.name,
      price: Number(variant.price),
      serves_people: variant.serves_people ? Number(variant.serves_people) : 1,
      is_active: variant.is_active
    })
    setIsVariantModalOpen(true)
  }

  // Variant Images Upload Handlers
  const handleUploadImage = async (file: File) => {
    if (!editingVariant) return
    setIsImageUploading(true)
    try {
      const isPrimary = variantImages.length === 0
      await productImagesService.upload(editingVariant.id, file, isPrimary)
      toast.success('Imagen subida con éxito.')
      
      // Reload images
      const res = await productImagesService.getByVariantId(editingVariant.id)
      setVariantImages(res.data?.data || [])
    } catch (err: any) {
      const message = err.response?.data?.message || 'Error al subir la imagen.'
      toast.error(message)
    } finally {
      setIsImageUploading(false)
    }
  }

  const handleMakePrimary = async (imgId: number) => {
    if (!editingVariant) return
    try {
      await productImagesService.setPrimary(imgId)
      toast.success('Foto marcada como principal.')
      
      // Reload images
      const res = await productImagesService.getByVariantId(editingVariant.id)
      setVariantImages(res.data?.data || [])
    } catch {
      toast.error('Error al actualizar imagen principal.')
    }
  }

  const handleDeleteImage = async (imgId: number) => {
    if (!editingVariant) return
    try {
      await productImagesService.delete(imgId)
      toast.success('Foto eliminada.')
      
      // Reload images
      const res = await productImagesService.getByVariantId(editingVariant.id)
      setVariantImages(res.data?.data || [])
    } catch {
      toast.error('Error al eliminar la foto.')
    }
  }

  // Table columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Product, index: number) => {
        const isCatInactive = _item.category && !_item.category.is_active
        return (
          <span className={cn(
            "font-sans font-bold text-text-sub/60",
            isCatInactive && "opacity-50"
          )}>
            {(page - 1) * perPage + index + 1}
          </span>
        )
      }
    },
    {
      header: 'Nombre',
      cell: (item: Product) => {
        const isCatInactive = item.category && !item.category.is_active
        return (
          <span className={cn(
            "font-heading font-black text-sm text-primary",
            isCatInactive && "text-text-sub/40 line-through select-none"
          )}>
            {item.name}
          </span>
        )
      }
    },
    {
      header: 'Categoría',
      cell: (item: Product) => {
        const isCatInactive = item.category && !item.category.is_active
        return item.category ? (
          <Badge variant={isCatInactive ? "neutral" : "info"} className={isCatInactive ? "opacity-60" : ""}>
            {item.category.name}
          </Badge>
        ) : (
          <span className="text-text-sub/50 italic text-[10px]">Sin categoría</span>
        )
      }
    },
    {
      header: 'Descripción',
      cell: (item: Product) => {
        const isCatInactive = item.category && !item.category.is_active
        return (
          <span className={cn(
            "text-text-sub block max-w-xs truncate",
            isCatInactive && "opacity-50"
          )}>
            {item.description || <span className="italic text-text-sub/40">Sin descripción</span>}
          </span>
        )
      }
    },
    {
      header: 'Estado',
      cell: (item: Product) => {
        const isCatInactive = item.category && !item.category.is_active
        return (
          <CrudStatusBadge
            isActive={item.is_active}
            onClick={hasPermission('products.update') && !isCatInactive ? () => toggleActiveMutation.mutate(item.id) : undefined}
            className={isCatInactive ? "opacity-40 cursor-not-allowed" : ""}
          />
        )
      }
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Product) => {
        const isCatInactive = item.category && !item.category.is_active
        const isInactive = !item.is_active || isCatInactive
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
              <Tooltip content={isCatInactive ? "La categoría de este producto está inactiva." : "Debe activar nuevamente el registro para editarlo."}>
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

  // Form Fields Definition
  const productFormFields = [
    {
      name: 'category_id',
      label: 'Categoría del Producto',
      type: 'select' as const,
      required: true,
      options: categories.map(cat => ({ value: cat.id, label: cat.name }))
    },
    {
      name: 'name',
      label: 'Nombre del Producto',
      type: 'text' as const,
      placeholder: 'Ej. Cheesecake de Frutos Rojos, Tarta Chilena...',
      required: true
    },
    {
      name: 'description',
      label: 'Descripción (Opcional)',
      type: 'textarea' as const,
      placeholder: 'Detalles sobre los ingredientes, porciones estándar o sabor.'
    },
    {
      name: 'is_active',
      label: 'Producto Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el producto se listará en la tienda principal.'
    }
  ]

  return (
    <CrudPage
      title="Catálogo de Productos"
      subtitle="Organiza las delicias culinarias y gestiona sus diferentes presentaciones y fotos."
      createLabel="Nuevo Producto"
      createPermission="products.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por nombre o descripción..."
    >
      <CrudTable
        data={products}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="productos"
      />

      {/* CREATE & EDIT FORM MODAL WITH TABS */}
      {isFormOpen && (
        <CrudModal
          isOpen={isFormOpen}
          onClose={closeFormModal}
          title={editingProduct ? `Gestionar Producto: ${editingProduct.name}` : 'Nuevo Producto'}
          size="lg"
        >
          <div className="space-y-6">
            {/* Tabs header if editing */}
            {editingProduct && (
              <Tabs
                activeTab={activeTab}
                onTabChange={setActiveTab}
                tabs={[
                  { id: 'general', label: 'Datos Generales' },
                  { id: 'variants', label: `Presentaciones / Tamaños (${variantsList.length})` }
                ]}
              />
            )}

            {/* TAB CONTENT: GENERAL INFO */}
            {activeTab === 'general' && (
              <CrudForm
                fields={productFormFields}
                form={productForm}
                onSubmit={(data) => saveProductMutation.mutate(data)}
                onCancel={closeFormModal}
                isPending={saveProductMutation.isPending}
              />
            )}

            {/* TAB CONTENT: PRODUCT VARIANTS / SUB-TAB */}
            {activeTab === 'variants' && editingProduct && (
              <div className="space-y-5 animate-fade-in">
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Typography variant="h4" className="text-sm font-bold text-primary">Tamaños y Precios Disponibles</Typography>
                    <p className="text-text-sub text-[10px]">Un producto debe tener al menos una presentación activa para aparecer en el menú público.</p>
                  </div>
                  <Button
                    type="button"
                    variant="primary"
                    size="sm"
                    onClick={openCreateVariantModal}
                    className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider py-2 px-3"
                  >
                    <FiPlus />
                    <span>Agregar Tamaño</span>
                  </Button>
                </div>

                <Card className="overflow-hidden border border-border shadow-sm">
                  <div className="overflow-x-auto">
                    <table className="w-full text-[10px] text-left border-collapse bg-surface">
                      <thead className="bg-stone-50 border-b border-border text-text-sub font-bold uppercase tracking-wider">
                        <tr>
                          <th className="px-4 py-3">Tamaño / Porción</th>
                          <th className="px-4 py-3">Precio</th>
                          <th className="px-4 py-3">Personas Aprox.</th>
                          <th className="px-4 py-3">Extras Relacionados</th>
                          <th className="px-4 py-3 text-center">Estado</th>
                          <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/60">
                        {variantsList.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="px-4 py-8 text-center italic text-text-sub/50 font-semibold bg-surface">
                              No hay presentaciones definidas para este producto. Agrega una para comenzar.
                            </td>
                          </tr>
                        ) : (
                          variantsList.map((variant) => (
                            <tr key={variant.id} className="hover:bg-stone-50/50 transition-colors">
                              <td className="px-4 py-3.5 font-bold text-text-main">{variant.name}</td>
                              <td className="px-4 py-3.5 font-semibold text-primary font-mono">Bs. {Number(variant.price).toFixed(2)}</td>
                              <td className="px-4 py-3.5 text-text-main font-semibold">
                                {variant.serves_people !== null && variant.serves_people !== undefined ? `${variant.serves_people} pers.` : <span className="italic text-text-sub/40">No especificado</span>}
                              </td>
                              <td className="px-4 py-3.5 text-text-sub">
                                {variant.extras && variant.extras.length > 0 ? (
                                  <div className="flex flex-wrap gap-1 max-w-xs">
                                    {variant.extras.map(e => (
                                      <Badge key={e.id} variant="neutral" className="text-[8px] px-1 py-0.5">
                                        {e.name} (Bs. {Number(e.price ?? 0).toFixed(2)})
                                      </Badge>
                                    ))}
                                  </div>
                                ) : (
                                  <span className="italic text-text-sub/40">Ninguno</span>
                                )}
                              </td>
                              <td className="px-4 py-3.5 text-center">
                                <CrudStatusBadge
                                  isActive={variant.is_active}
                                  onClick={() => toggleActiveVariantMutation.mutate(variant.id)}
                                />
                              </td>
                              <td className="px-4 py-3.5 text-right">
                                {(() => {
                                  const isVariantInactive = !variant.is_active
                                  const editVariantButton = (
                                    <Button
                                      type="button"
                                      variant="secondary"
                                      size="sm"
                                      onClick={isVariantInactive ? undefined : () => openEditVariantModal(variant)}
                                      className={cn(
                                        "inline-flex items-center gap-1.5",
                                        isVariantInactive && "bg-stone-200 hover:bg-stone-200 text-stone-400 cursor-not-allowed opacity-60 active:scale-100 dark:bg-stone-800 dark:text-stone-600"
                                      )}
                                    >
                                      <FiEdit2 className="text-xs" />
                                      <span>Editar y Foto</span>
                                    </Button>
                                  )

                                  return (
                                    <div className="inline-block">
                                      {isVariantInactive ? (
                                        <Tooltip content="Debe activar nuevamente el registro para editarlo.">
                                          {editVariantButton}
                                        </Tooltip>
                                      ) : (
                                        editVariantButton
                                      )}
                                    </div>
                                  )
                                })()}
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </Card>
              </div>
            )}
          </div>
        </CrudModal>
      )}

      {/* DYNAMIC PRESENTATION / VARIANT DIALOG */}
      {isVariantModalOpen && (
        <div className="fixed inset-0 z-55 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm animate-fade-in overflow-y-auto">
          <div className="bg-surface w-full max-w-2xl rounded-lg border border-border shadow-2xl p-6 sm:p-7 space-y-6 relative overflow-hidden animate-scale-up my-8">
            <div className="flex items-center justify-between border-b border-border/60 pb-4">
              <Typography variant="h3">
                {editingVariant ? `Editar Presentación: ${editingVariant.name}` : 'Nueva Presentación'}
              </Typography>
              <button 
                type="button" 
                onClick={() => setIsVariantModalOpen(false)}
                className="text-text-sub hover:text-primary text-base transition-colors p-1 flex items-center justify-center"
              >
                <FiX />
              </button>
            </div>

            <form onSubmit={variantForm.handleSubmit((data) => saveVariantMutation.mutate(data))} className="space-y-6">
              {/* BLOCK 1: INFORMACIÓN GENERAL & PRECIO */}
              <div className="bg-surface border border-border/80 p-5 rounded-lg space-y-5">
                <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Información General</Typography>
                <div className="space-y-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="v_name" required>Nombre del Tamaño / Porciones</Label>
                    <Input
                      id="v_name"
                      placeholder="Ej. Personal, 12 porciones, Familiar..."
                      error={variantForm.formState.errors.name?.message}
                      {...variantForm.register('name')}
                    />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <Label htmlFor="v_price" required>Precio de Venta (Bs.)</Label>
                      <Input
                        id="v_price"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        error={variantForm.formState.errors.price?.message}
                        {...variantForm.register('price')}
                      />
                    </div>
                    <div className="space-y-1.5">
                      <Label htmlFor="v_serves_people">Cantidad de Personas aproximada (Opcional)</Label>
                      <Input
                        id="v_serves_people"
                        type="number"
                        placeholder="Ej. 6, 12, 25..."
                        error={variantForm.formState.errors.serves_people?.message}
                        {...variantForm.register('serves_people')}
                      />
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-3 bg-background border border-border p-4 rounded-lg">
                  <input
                    id="v_is_active"
                    type="checkbox"
                    {...variantForm.register('is_active')}
                    className="w-4 h-4 text-primary border-border rounded focus:ring-stone-200"
                  />
                  <div className="text-[10px]">
                    <label htmlFor="v_is_active" className="font-bold text-text-main block cursor-pointer">
                      Presentación Activa
                    </label>
                    <span className="text-text-sub">
                      Determina si esta presentación específica estará disponible para la compra.
                    </span>
                  </div>
                </div>
              </div>

              {/* BLOCK 2: GALERÍA / FOTOGRAFÍA DE LA PRESENTACIÓN */}
              {editingVariant ? (
                <div className="bg-surface border border-border/80 p-5 rounded-lg space-y-4">
                  <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Galería de Imágenes</Typography>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    <div>
                      <ImageUploader
                        isLoading={isImageUploading}
                        onFileSelect={handleUploadImage}
                      />
                    </div>
                    <div>
                      {variantImages.length === 0 ? (
                        <div className="h-full min-h-[150px] bg-background border border-border rounded-lg flex flex-col items-center justify-center p-6 text-center">
                          <FiImage className="text-3xl text-text-sub/40 mb-2 stroke-[1.5]" />
                          <p className="text-[10px] text-text-sub font-semibold">Sin imágenes cargadas para este tamaño.</p>
                        </div>
                      ) : (
                        <div className="grid grid-cols-2 gap-3">
                          {variantImages.map((img) => (
                            <ImagePreview
                              key={img.id}
                              src={img.image_url}
                              isPrimary={img.is_primary}
                              onMakePrimary={() => handleMakePrimary(img.id)}
                              onDelete={() => handleDeleteImage(img.id)}
                            />
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ) : (
                <div className="bg-stone-50 border border-border/80 p-4 rounded-lg text-[10px] text-text-sub font-bold leading-normal">
                  Al guardar los datos de esta presentación, podrás subir y gestionar sus fotos de portada en este mismo formulario.
                </div>
              )}

              {/* BLOCK 3: EXTRAS / ADICIONALES RELACIONADOS */}
              {editingVariant && extrasList.length > 0 && (
                <div className="bg-surface border border-border/80 p-5 rounded-lg space-y-3">
                  <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Adicionales / Coberturas Relacionadas</Typography>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {extrasList.map((extra) => {
                      const existingExtra = selectedExtrasWithPrices.find(e => e.extra_id === extra.id)
                      const isChecked = !!existingExtra
                      const extraPrice = existingExtra ? existingExtra.price : 0

                      return (
                        <div key={extra.id} className="flex flex-col gap-2 p-2.5 rounded-lg border border-border bg-background/50 hover:bg-stone-100/50 transition-colors">
                          <div className="flex items-center gap-2">
                            <input
                              type="checkbox"
                              id={`extra-${extra.id}`}
                              checked={isChecked}
                              onChange={(e) => {
                                if (e.target.checked) {
                                  setSelectedExtrasWithPrices(prev => [...prev, { extra_id: extra.id, price: 0 }])
                                } else {
                                  setSelectedExtrasWithPrices(prev => prev.filter(item => item.extra_id !== extra.id))
                                }
                              }}
                              className="w-3.5 h-3.5 text-primary border-border rounded focus:ring-stone-250/20"
                            />
                            <label htmlFor={`extra-${extra.id}`} className="text-[10px] font-bold text-text-main cursor-pointer select-none leading-none">
                              {extra.name}
                            </label>
                          </div>
                          {isChecked && (
                            <div className="flex items-center gap-1.5 mt-1 bg-stone-50 border border-stone-200/60 p-1 rounded">
                              <span className="text-[9px] text-text-sub font-semibold">Precio:</span>
                              <input
                                type="number"
                                step="0.01"
                                min="0"
                                value={extraPrice}
                                onChange={(e) => {
                                  const val = Number(e.target.value)
                                  setSelectedExtrasWithPrices(prev => prev.map(item => 
                                    item.extra_id === extra.id ? { ...item, price: val } : item
                                  ))
                                }}
                                className="w-16 px-1 py-0.5 border border-border rounded text-[10px] outline-none focus:border-primary bg-surface font-semibold text-primary"
                              />
                              <span className="text-[9px] text-text-sub font-semibold">Bs</span>
                            </div>
                          )}
                        </div>
                      )
                    })}
                  </div>
                </div>
              )}

              {/* ACCIONES (FOOTER) */}
              <div className="flex items-center justify-end gap-3 border-t border-border/60 pt-5">
                <Button
                  type="button"
                  variant="neutral"
                  onClick={() => setIsVariantModalOpen(false)}
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  variant="primary"
                  isLoading={saveVariantMutation.isPending}
                >
                  Guardar Presentación
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </CrudPage>
  )
}
