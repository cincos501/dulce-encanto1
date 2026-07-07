import React, { useState, useEffect } from 'react'
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
  Typography
} from '@/design-system'
import productsService from '@/shared/services/productsService'
import categoriesService from '@/shared/services/categoriesService'
import productVariantsService from '@/shared/services/productVariantsService'
import productImagesService from '@/shared/services/productImagesService'
import extrasService from '@/shared/services/extrasService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Product, Category, ProductVariant, ProductImage, Extra } from '@/shared/types'
import { FiEdit2, FiTrash2, FiPlus, FiInfo, FiImage, FiX } from 'react-icons/fi'

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
  base_price: z.preprocess(
    (val) => (val === '' ? undefined : Number(val)),
    z.number({ invalid_type_error: 'El precio debe ser un número.' }).min(0.01, 'El precio debe ser mayor a 0.')
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
  const [isDeleteOpen, setIsDeleteOpen] = useState<boolean>(false)
  const [productToDelete, setProductToDelete] = useState<Product | null>(null)

  // Sub-resource states (only active when editing)
  const [variantsList, setVariantsList] = useState<ProductVariant[]>([])
  const [isVariantModalOpen, setIsVariantModalOpen] = useState<boolean>(false)
  const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null)
  const [isVariantDeleteOpen, setIsVariantDeleteOpen] = useState<boolean>(false)
  const [variantToDelete, setVariantToDelete] = useState<ProductVariant | null>(null)

  // Variant Image state
  const [variantImages, setVariantImages] = useState<ProductImage[]>([])
  const [isImageUploading, setIsImageUploading] = useState<boolean>(false)

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
      base_price: 0,
      is_active: true
    }
  })

  // Queries
  const { data: categoriesData } = useQuery({
    queryKey: ['categories-list-select'],
    queryFn: async () => {
      const response = await categoriesService.getAll()
      return response.data?.data || []
    }
  })

  const categories = (categoriesData as Category[]) || []

  const { data: extrasData } = useQuery({
    queryKey: ['extras-list-product-form'],
    queryFn: async () => {
      const response = await extrasService.getAll()
      return response.data?.data || []
    }
  })

  const extrasList = (extrasData as Extra[]) || []

  const { data: queryData, isLoading, isError, error } = useQuery({
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
      toast.success(editingProduct ? 'Producto actualizado con éxito.' : 'Producto creado con éxito.')
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
      const message = err.response?.data?.message || 'Error al guardar el producto.'
      toast.error(message)
    }
  })

  const deleteProductMutation = useMutation({
    mutationFn: (id: number) => productsService.delete(id),
    onSuccess: () => {
      toast.success('Producto eliminado con éxito.')
      queryClient.invalidateQueries({ queryKey: ['products'] })
      setIsDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se pudo eliminar el producto.'
      toast.error(message)
    }
  })

  // Mutation: Variant Store/Update
  const saveVariantMutation = useMutation({
    mutationFn: async (data: VariantFormInputs) => {
      if (!editingProduct) return
      const payload = {
        product_id: editingProduct.id,
        name: data.name,
        base_price: data.base_price,
        is_active: data.is_active
      }
      if (editingVariant) {
        return productVariantsService.update(editingVariant.id, payload)
      } else {
        return productVariantsService.create(payload)
      }
    },
    onSuccess: () => {
      toast.success(editingVariant ? 'Presentación actualizada.' : 'Presentación agregada.')
      if (editingProduct) loadSubResources(editingProduct.id)
      setIsVariantModalOpen(false)
      setEditingVariant(null)
      variantForm.reset()
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'Error al guardar la variante.'
      toast.error(message)
    }
  })

  const deleteVariantMutation = useMutation({
    mutationFn: (id: number) => productVariantsService.delete(id),
    onSuccess: () => {
      toast.success('Presentación eliminada con éxito.')
      if (editingProduct) loadSubResources(editingProduct.id)
      setIsVariantDeleteOpen(false)
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || 'No se puede eliminar la variante.'
      toast.error(message)
    }
  })

  // Mutation: Toggle Active Product
  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) => productsService.toggleActive(id),
    onSuccess: () => {
      toast.success('Estado del producto actualizado.')
      queryClient.invalidateQueries({ queryKey: ['products'] })
    },
    onError: () => toast.error('Error al actualizar el estado.')
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
    setActiveTab('general')
    productForm.reset()
  }

  // Variant editing handlers
  const openCreateVariantModal = () => {
    setEditingVariant(null)
    setVariantImages([])
    variantForm.reset({
      name: '',
      base_price: 0,
      is_active: true
    })
    setIsVariantModalOpen(true)
  }

  const openEditVariantModal = async (variant: ProductVariant) => {
    setEditingVariant(variant)
    variantForm.reset({
      name: variant.name,
      base_price: variant.base_price,
      is_active: variant.is_active
    })
    
    // Fetch variant images
    try {
      const res = await productImagesService.getByVariantId(variant.id)
      setVariantImages(res.data?.data || [])
    } catch {
      setVariantImages([])
    }

    setIsVariantModalOpen(true)
  }

  // Variant Image management
  const handleUploadImage = async (file: File) => {
    if (!editingVariant) return
    setIsImageUploading(true)
    try {
      const isPrimary = variantImages.length === 0
      await productImagesService.upload(editingVariant.id, file, isPrimary)
      toast.success('Foto cargada exitosamente.')
      
      // Reload images
      const res = await productImagesService.getByVariantId(editingVariant.id)
      setVariantImages(res.data?.data || [])
    } catch {
      toast.error('Error al subir la imagen.')
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
      header: 'ID',
      cell: (item: Product) => <span className="font-bold text-text-sub/60">#{item.id}</span>
    },
    {
      header: 'Nombre',
      cell: (item: Product) => <span className="font-heading font-black text-sm text-primary">{item.name}</span>
    },
    {
      header: 'Categoría',
      cell: (item: Product) => (
        item.category ? (
          <Badge variant="info">{item.category.name}</Badge>
        ) : (
          <span className="text-text-sub/50 italic text-[10px]">Sin categoría</span>
        )
      )
    },
    {
      header: 'Descripción',
      cell: (item: Product) => (
        <span className="text-text-sub block max-w-xs truncate">
          {item.description || <span className="italic text-text-sub/40">Sin descripción</span>}
        </span>
      )
    },
    {
      header: 'Estado',
      cell: (item: Product) => (
        <CrudStatusBadge
          isActive={item.is_active}
          onClick={hasPermission('products.update') ? () => toggleActiveMutation.mutate(item.id) : undefined}
        />
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Product) => (
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
          {hasPermission('products.delete') && (
            <Button
              variant="ghost"
              size="sm"
              className="text-red-650 hover:bg-red-50 inline-flex items-center gap-1.5"
              onClick={() => {
                setProductToDelete(item);
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
      placeholder: 'Ej. Cheesecake de Oreo, Budín de Naranja...',
      required: true
    },
    {
      name: 'description',
      label: 'Descripción (Opcional)',
      type: 'textarea' as const,
      placeholder: 'Detalles sobre ingredientes, decoración, etc.'
    },
    {
      name: 'is_active',
      label: 'Producto Activo',
      type: 'switch' as const,
      placeholder: 'Determina si el producto será visible en el catálogo público.'
    }
  ]

  return (
    <CrudPage
      title="Catálogo de Productos"
      subtitle="Gestiona el catálogo general de productos de la pastelería, sus tamaños, precios y fotos en un mismo lugar."
      createLabel="Nuevo Producto"
      createPermission="products.create"
      onCreateClick={openCreateModal}
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar productos por nombre, categoría..."
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

      {/* DYNAMIC PRODUCT DIALOG */}
      {isFormOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm animate-fade-in overflow-y-auto">
          <div className="bg-surface w-full max-w-4xl rounded-lg border border-border shadow-2xl p-6 sm:p-7 space-y-6 relative overflow-hidden animate-scale-up my-8">
            
            {/* Header */}
            <div className="flex items-center justify-between border-b border-border/60 pb-4">
              <div className="space-y-0.5">
                <Typography variant="h3">
                  {editingProduct ? `Editor de Producto: ${editingProduct.name}` : 'Nuevo Producto'}
                </Typography>
                {editingProduct && (
                  <p className="text-text-sub text-[10px] font-bold uppercase tracking-wider">ID Producto: #{editingProduct.id}</p>
                )}
              </div>
              <button 
                type="button" 
                onClick={closeFormModal}
                className="text-text-sub hover:text-primary text-base transition-colors p-1 flex items-center justify-center"
              >
                <FiX />
              </button>
            </div>

            {/* TAB SELECTOR (Only when product exists) */}
            {editingProduct ? (
              <Tabs
                tabs={[
                  { id: 'general', label: '1. Información General', icon: null },
                  { id: 'variants', label: '2. Presentaciones y Precios', icon: null }
                ]}
                activeTab={activeTab}
                onTabChange={setActiveTab}
              />
            ) : (
              <div className="bg-secondary/15 border border-secondary/30 p-4 rounded-lg flex items-center gap-3">
                <FiInfo className="text-sm shrink-0" />
                <p className="text-[10px] text-primary/80 leading-normal font-bold font-sans">
                  Al completar y guardar los datos generales del producto, podrás cargar sus tamaños, precios e imágenes correspondientes.
                </p>
              </div>
            )}

            {/* TAB CONTENTS */}
            {activeTab === 'general' && (
              <div className="bg-surface border border-border/80 rounded-lg p-5">
                <CrudForm
                  fields={productFormFields}
                  form={productForm}
                  onSubmit={(data) => saveProductMutation.mutate(data)}
                  onCancel={closeFormModal}
                  isPending={saveProductMutation.isPending}
                  submitLabel={editingProduct ? 'Guardar Cambios' : 'Guardar y Continuar'}
                />
              </div>
            )}

            {activeTab === 'variants' && editingProduct && (
              <div className="space-y-6">
                
                {/* Header sub-table */}
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Precios y Presentaciones registradas</Typography>
                    <p className="text-text-sub text-[10px] font-semibold">Define tamaños y precios asociados a este producto.</p>
                  </div>
                  <Button
                    type="button"
                    variant="primary"
                    size="sm"
                    onClick={openCreateVariantModal}
                    className="inline-flex items-center gap-1.5"
                  >
                    <FiPlus />
                    <span>Añadir Presentación</span>
                  </Button>
                </div>

                {/* Sub-table list */}
                <Card>
                  <div className="overflow-x-auto w-full">
                    <table className="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr className="bg-background border-b border-border/85 text-[10px] font-bold text-text-sub uppercase tracking-widest">
                          <th className="px-4 py-3.5">ID</th>
                          <th className="px-4 py-3.5">Código SKU</th>
                          <th className="px-4 py-3.5 font-bold">Presentación</th>
                          <th className="px-4 py-3.5 text-center">Precio Base</th>
                          <th className="px-4 py-3.5 text-center">Estado</th>
                          <th className="px-4 py-3.5 text-right">Acciones</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/40 text-text-main">
                        {variantsList.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="py-12 text-center text-text-sub/50 italic font-semibold">
                              No se han registrado presentaciones para este producto.
                            </td>
                          </tr>
                        ) : (
                          variantsList.map((variant) => (
                            <tr key={variant.id} className="hover:bg-stone-50/50 transition-colors">
                              <td className="px-4 py-3.5 font-sans font-bold text-text-sub/60">#{variant.id}</td>
                              <td className="px-4 py-3.5 font-sans font-bold text-stone-600">{variant.sku}</td>
                              <td className="px-4 py-3.5 font-bold text-primary">{variant.name}</td>
                              <td className="px-4 py-3.5 text-center font-sans font-bold text-primary">
                                ${Number(variant.base_price).toFixed(2)}
                              </td>
                              <td className="px-4 py-3.5 text-center">
                                <Badge variant={variant.is_active ? 'success' : 'neutral'}>
                                  {variant.is_active ? 'Activo' : 'Inactivo'}
                                </Badge>
                              </td>
                              <td className="px-4 py-3.5 text-right space-x-2">
                                <Button
                                  type="button"
                                  variant="secondary"
                                  size="sm"
                                  onClick={() => openEditVariantModal(variant)}
                                  className="inline-flex items-center gap-1.5"
                                >
                                  <FiEdit2 className="text-xs" />
                                  <span>Editar y Foto</span>
                                </Button>
                                <Button
                                  type="button"
                                  variant="ghost"
                                  size="sm"
                                  className="text-red-650 inline-flex items-center gap-1.5"
                                  onClick={() => {
                                    setVariantToDelete(variant);
                                    setIsVariantDeleteOpen(true);
                                  }}
                                >
                                  <FiTrash2 className="text-xs" />
                                  <span>Eliminar</span>
                                </Button>
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
        </div>
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
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="v_name" required>Nombre del Tamaño / Porciones</Label>
                    <Input
                      id="v_name"
                      placeholder="Ej. Personal, 12 porciones, Familiar..."
                      error={variantForm.formState.errors.name?.message}
                      {...variantForm.register('name')}
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="v_price" required>Precio de Venta ($)</Label>
                    <Input
                      id="v_price"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                      error={variantForm.formState.errors.base_price?.message}
                      {...variantForm.register('base_price')}
                    />
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
                    {extrasList.map((extra) => (
                      <div key={extra.id} className="flex items-center gap-2 p-2.5 rounded-lg border border-border bg-background/50 hover:bg-stone-100/50 transition-colors">
                        <input
                          type="checkbox"
                          id={`extra-${extra.id}`}
                          className="w-3.5 h-3.5 text-primary border-border rounded focus:ring-stone-250/20"
                        />
                        <label htmlFor={`extra-${extra.id}`} className="text-[10px] font-bold text-text-main cursor-pointer select-none leading-none">
                          {extra.name} <span className="text-text-sub/70 block mt-0.5">+${Number(extra.price).toFixed(2)}</span>
                        </label>
                      </div>
                    ))}
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

      {/* CONFIRM DELETE PRODUCT DIALOG */}
      <CrudDeleteDialog
        isOpen={isDeleteOpen}
        onClose={() => setIsDeleteOpen(false)}
        onConfirm={() => productToDelete && deleteProductMutation.mutate(productToDelete.id)}
        isLoading={deleteProductMutation.isPending}
        title="¿Eliminar Producto?"
        message={`Estás seguro de que deseas eliminar permanentemente el producto "${productToDelete?.name}"? Esta acción no se puede deshacer.`}
      />

      {/* CONFIRM DELETE VARIANT DIALOG */}
      <CrudDeleteDialog
        isOpen={isVariantDeleteOpen}
        onClose={() => setIsVariantDeleteOpen(false)}
        onConfirm={() => variantToDelete && deleteVariantMutation.mutate(variantToDelete.id)}
        isLoading={deleteVariantMutation.isPending}
        title="¿Eliminar Presentación?"
        message={`Estás seguro de que deseas eliminar permanentemente la presentación "${variantToDelete?.name}"? Esta acción no se puede deshacer.`}
      />
    </CrudPage>
  )
}
