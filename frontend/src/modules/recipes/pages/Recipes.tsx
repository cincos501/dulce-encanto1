import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { 
  CrudPage, 
  CrudTable, 
  CrudStatusBadge,
  CrudModal
} from '@/shared/components/crud'
import { Badge, Button, Tooltip, Input, Select, Divider, Typography } from '@/design-system'
import recipesService from '@/shared/services/recipesService'
import suppliesService from '@/shared/services/suppliesService'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { Recipe, RecipeItem, Supply } from '@/shared/types'
import { FiEdit2, FiPlus, FiTrash2 } from 'react-icons/fi'
import { cn } from '@/shared/utils/cn'
import { toast } from 'sonner'

export default function Recipes() {
  const queryClient = useQueryClient()
  const { hasPermission } = useAuthorization()

  // Local filters
  const [search, setSearch] = useState<string>('')
  const [page, setPage] = useState<number>(1)
  const perPage = 10

  // Modal states
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false)
  const [selectedVariant, setSelectedVariant] = useState<Recipe | null>(null)
  
  // Custom form state for recipe items
  const [recipeItems, setRecipeItems] = useState<RecipeItem[]>([])
  const [backendErrors, setBackendErrors] = useState<Record<string, string>>({})

  // Queries
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['recipes', page, search],
    queryFn: async () => {
      const response = await recipesService.paginate(page, search, perPage)
      return response.data
    }
  })

  const { data: suppliesData } = useQuery({
    queryKey: ['active-supplies'],
    queryFn: async () => {
      const response = await suppliesService.getAll(true) // only active
      return response.data?.data || []
    }
  })

  const recipes = (queryData?.data as Recipe[]) || []
  const pagination = queryData?.meta || { current_page: 1, last_page: 1, total: 0 }
  const activeSupplies = (suppliesData as Supply[]) || []

  // Mutation: Save recipe
  const saveRecipeMutation = useMutation({
    mutationFn: async () => {
      if (!selectedVariant) return
      
      const payload = {
        items: recipeItems.map(item => ({
          supply_id: item.supply_id,
          quantity: item.quantity,
          unit: item.unit,
          observation: item.observation || null
        }))
      }

      return recipesService.saveRecipe(selectedVariant.id, payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['recipes'] })
      toast.success('Receta guardada con éxito.')
      closeModal()
    },
    onError: (err: any) => {
      if (err.response?.status === 422) {
        const errors = err.response?.data?.errors || {}
        const mappedErrors: Record<string, string> = {}
        Object.keys(errors).forEach(key => {
          mappedErrors[key] = errors[key][0]
        })
        setBackendErrors(mappedErrors)
        toast.error('Hay errores en el formulario.')
      } else {
        const msg = err.response?.data?.message || 'Error al guardar la receta.'
        toast.error(msg)
      }
    }
  })

  // Open & Close Modal
  const openEditModal = (recipe: Recipe) => {
    setSelectedVariant(recipe)
    setRecipeItems(recipe.items || [])
    setBackendErrors({})
    setIsModalOpen(true)
  }

  const closeModal = () => {
    setIsModalOpen(false)
    setSelectedVariant(null)
    setRecipeItems([])
    setBackendErrors({})
  }

  // Row operations
  const addIngredientRow = () => {
    // Find the first supply that hasn't been selected yet
    const selectedIds = recipeItems.map(item => item.supply_id)
    const availableSupply = activeSupplies.find(s => !selectedIds.includes(s.id)) || activeSupplies[0]

    if (!availableSupply) {
      toast.error('No hay insumos disponibles para agregar.')
      return
    }

    setRecipeItems(prev => [
      ...prev,
      {
        supply_id: availableSupply.id,
        supply_name: availableSupply.name,
        quantity: 1,
        unit: availableSupply.unit,
        observation: ''
      }
    ])
  }

  const removeIngredientRow = (index: number) => {
    setRecipeItems(prev => prev.filter((_, idx) => idx !== index))
  }

  const handleSupplyChange = (index: number, supplyId: number) => {
    const supply = activeSupplies.find(s => s.id === supplyId)
    if (!supply) return

    setRecipeItems(prev => prev.map((item, idx) => 
      idx === index 
        ? { 
            ...item, 
            supply_id: supply.id, 
            supply_name: supply.name, 
            unit: supply.unit 
          } 
        : item
    ))
  }

  const handleQuantityChange = (index: number, qty: number) => {
    setRecipeItems(prev => prev.map((item, idx) => 
      idx === index ? { ...item, quantity: qty } : item
    ))
  }

  const handleObservationChange = (index: number, obs: string) => {
    setRecipeItems(prev => prev.map((item, idx) => 
      idx === index ? { ...item, observation: obs } : item
    ))
  }

  // Columns definition
  const columns = [
    {
      header: 'Número',
      cell: (_item: Recipe, index: number) => <span className="font-sans font-bold text-text-sub/60">{(page - 1) * perPage + index + 1}</span>
    },
    {
      header: 'Producto',
      cell: (item: Recipe) => <span className="font-heading font-black text-sm text-primary">{item.product.name}</span>
    },
    {
      header: 'Presentación',
      cell: (item: Recipe) => <Badge variant="info">{item.name}</Badge>
    },
    {
      header: 'SKU',
      cell: (item: Recipe) => <span className="font-sans text-xs font-semibold text-text-sub">{item.sku}</span>
    },
    {
      header: 'Insumos / Ingredientes',
      cell: (item: Recipe) => {
        const count = item.items?.length || 0
        return (
          <span className="font-sans font-bold text-xs">
            {count === 1 ? '1 insumo' : `${count} insumos`}
          </span>
        )
      }
    },
    {
      header: 'Estado Presentación',
      cell: (item: Recipe) => (
        <span className={cn(
          "inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider select-none",
          item.is_active 
            ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400" 
            : "bg-stone-100 text-stone-600 dark:bg-stone-850 dark:text-stone-400"
        )}>
          {item.is_active ? 'Activa' : 'Inactiva'}
        </span>
      )
    },
    {
      header: 'Acciones',
      headerClassName: 'text-right',
      cellClassName: 'text-right space-x-2',
      cell: (item: Recipe) => {
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
            <span>Configurar Receta</span>
          </Button>
        )

        return (
          <div className="inline-block">
            {isInactive ? (
              <Tooltip content="Debe activar nuevamente la presentación para editar su receta.">
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

  // Prepare supply options for selectors
  const getSupplyOptions = (index: number) => {
    const selectedIds = recipeItems
      .filter((_, idx) => idx !== index)
      .map(item => item.supply_id)

    return activeSupplies.map(s => ({
      value: s.id,
      label: `${s.name} (${s.unit})` + (selectedIds.includes(s.id) ? ' (Ya seleccionado)' : '')
    }))
  }

  return (
    <CrudPage
      title="Recetas de Producción"
      subtitle="Define los insumos y las cantidades exactas requeridas para fabricar una unidad de cada presentación."
      createPermission="recipes.create"
      onCreateClick={undefined} // No create button, recipes are mapped directly to variants
      search={search}
      onSearchChange={(val) => { setSearch(val); setPage(1); }}
      searchPlaceholder="Buscar por producto, presentación o SKU..."
    >
      <CrudTable
        data={recipes}
        columns={columns}
        isLoading={isLoading}
        currentPage={pagination.current_page}
        lastPage={pagination.last_page}
        total={pagination.total}
        onPageChange={setPage}
        label="recetas"
      />

      {/* RECIPE CONFIG MODAL */}
      {isModalOpen && selectedVariant && (
        <CrudModal
          isOpen={isModalOpen}
          onClose={closeModal}
          title="Configurar Receta"
          maxWidthClassName="max-w-4xl"
        >
          <div className="space-y-6 font-sans">
            {/* Variant Information */}
            <div className="bg-stone-50 dark:bg-stone-900 border border-border p-4 rounded-lg flex items-center justify-between">
              <div>
                <Typography variant="label" className="text-xs uppercase tracking-wider font-bold">Presentación</Typography>
                <h3 className="font-heading font-black text-base text-primary">
                  {selectedVariant.product.name} — <span className="text-text-sub font-semibold">{selectedVariant.name}</span>
                </h3>
              </div>
              <div className="text-right">
                <span className="text-[10px] text-text-sub block font-bold uppercase tracking-widest">SKU</span>
                <span className="font-sans font-bold text-xs text-text-main">{selectedVariant.sku}</span>
              </div>
            </div>

            <Divider />

            {/* Ingredients Header & Actions */}
            <div className="flex items-center justify-between">
              <div>
                <Typography variant="h4" className="font-heading font-black text-sm text-text-main">Insumos Requeridos</Typography>
                <p className="text-text-sub text-[10px]">Define la cantidad de insumos consumida para fabricar 1 unidad de esta presentación.</p>
              </div>
              {hasPermission('recipes.update') && (
                <Button 
                  size="sm" 
                  onClick={addIngredientRow} 
                  className="text-[10px] uppercase font-bold tracking-wider gap-1.5"
                >
                  <FiPlus className="text-xs" />
                  Agregar Insumo
                </Button>
              )}
            </div>

            {/* General errors message */}
            {backendErrors['items'] && (
              <p className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/30 p-2.5 rounded border border-red-200">
                {backendErrors['items']}
              </p>
            )}

            {/* Recipe rows list */}
            <div className="space-y-3 max-h-96 overflow-y-auto pr-1">
              {recipeItems.length === 0 ? (
                <div className="text-center py-8 border border-dashed border-border rounded-lg bg-background/50">
                  <p className="text-xs text-text-sub/50 italic font-semibold">Esta presentación no tiene insumos configurados.</p>
                  {hasPermission('recipes.update') && (
                    <Button size="sm" onClick={addIngredientRow} className="mt-3 text-[10px] uppercase tracking-wider">
                      Crear primera receta
                    </Button>
                  )}
                </div>
              ) : (
                recipeItems.map((item, index) => {
                  const selectErrKey = `items.${index}.supply_id`
                  const qtyErrKey = `items.${index}.quantity`
                  const unitErrKey = `items.${index}.unit`
                  const obsErrKey = `items.${index}.observation`

                  return (
                    <div 
                      key={index} 
                      className="bg-surface border border-border/80 p-4 rounded-lg flex flex-col md:flex-row md:items-start gap-4 hover:shadow-md transition-shadow relative animate-fade-in"
                    >
                      {/* Supply Selector */}
                      <div className="flex-grow space-y-1">
                        <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Insumo</label>
                        <Select
                          options={getSupplyOptions(index)}
                          value={item.supply_id || ''}
                          onChange={(e) => handleSupplyChange(index, Number(e.target.value))}
                          placeholder="Seleccionar Insumo..."
                          error={backendErrors[selectErrKey]}
                          disabled={!hasPermission('recipes.update')}
                        />
                      </div>

                      {/* Quantity Input */}
                      <div className="w-full md:w-32 space-y-1">
                        <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Cantidad</label>
                        <div className="relative">
                          <Input
                            type="number"
                            step="0.0001"
                            placeholder="0.0000"
                            value={item.quantity || ''}
                            onChange={(e) => handleQuantityChange(index, Number(e.target.value))}
                            error={!!backendErrors[qtyErrKey]}
                            disabled={!hasPermission('recipes.update')}
                            className="pr-10"
                            required
                          />
                          <span className="absolute right-3 top-2.5 text-xs font-bold text-text-sub pointer-events-none select-none">
                            {item.unit}
                          </span>
                        </div>
                        {backendErrors[qtyErrKey] && (
                          <span className="text-[10px] text-red-500 font-semibold pl-1">
                            {backendErrors[qtyErrKey]}
                          </span>
                        )}
                      </div>

                      {/* Observation */}
                      <div className="flex-grow space-y-1">
                        <label className="text-[10px] text-text-sub font-bold uppercase tracking-wider select-none">Observación (Opcional)</label>
                        <Input
                          type="text"
                          placeholder="Ej. Cernir antes de pesar, trocear..."
                          value={item.observation || ''}
                          onChange={(e) => handleObservationChange(index, e.target.value)}
                          error={!!backendErrors[obsErrKey]}
                          disabled={!hasPermission('recipes.update')}
                        />
                      </div>

                      {/* Action */}
                      {hasPermission('recipes.update') && (
                        <div className="pt-6 shrink-0">
                          <Button
                            variant="secondary"
                            onClick={() => removeIngredientRow(index)}
                            className="bg-red-50 hover:bg-red-100 text-red-650 hover:text-red-700 p-2.5 rounded-lg border border-red-200 flex items-center justify-center cursor-pointer"
                            title="Eliminar fila"
                          >
                            <FiTrash2 className="text-sm" />
                          </Button>
                        </div>
                      )}
                    </div>
                  )
                })
              )}
            </div>

            <Divider />

            {/* Modal Actions */}
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={closeModal}>
                Cancelar
              </Button>
              {hasPermission('recipes.update') && recipeItems.length > 0 && (
                <Button 
                  onClick={() => saveRecipeMutation.mutate()} 
                  disabled={saveRecipeMutation.isPending}
                  className="font-bold tracking-wider"
                >
                  {saveRecipeMutation.isPending ? 'Guardando...' : 'Guardar Receta'}
                </Button>
              )}
            </div>
          </div>
        </CrudModal>
      )}
    </CrudPage>
  )
}
