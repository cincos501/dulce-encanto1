import React, { useState, useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import catalogService from '@/shared/services/catalogService'
import { CatalogItem, CatalogPromotion } from '@/shared/types'
import { 
  Button, 
  Card, 
  CardContent, 
  Badge, 
  Typography, 
  PriceTag, 
  Modal, 
  EmptyState, 
  Loading 
} from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiSearch, FiInfo, FiCheck, FiTag, FiShoppingBag } from 'react-icons/fi'

export default function Menu() {
  const [activeCategory, setActiveCategory] = useState<string>('todos')
  const [searchQuery, setSearchQuery] = useState<string>('')
  const [cartCount, setCartCount] = useState<number>(0)
  const [selectedProductId, setSelectedProductId] = useState<number | null>(null)
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(null)
  const [activeImageIndex, setActiveImageIndex] = useState<number>(0)

  // React Query: Get public catalog
  const { data: catalogData, isLoading, isError } = useQuery({
    queryKey: ['public-catalog'],
    queryFn: async () => {
      const response = await catalogService.getCatalog()
      return response.data?.data || []
    },
    staleTime: 5000
  })

  // React Query: Get product details when modal is open
  const { data: detailData, isLoading: isLoadingDetail } = useQuery({
    queryKey: ['public-product-detail', selectedProductId],
    queryFn: async () => {
      if (!selectedProductId) return null
      const response = await catalogService.getProductDetail(Number(selectedProductId))
      return response.data?.data || null
    },
    enabled: !!selectedProductId,
    staleTime: 5000
  })

  useEffect(() => {
    if (detailData) {
      if (detailData.variants && detailData.variants.length > 0) {
        setSelectedVariantId(detailData.variants[0].id)
      }
      setActiveImageIndex(0)
    }
  }, [detailData])

  const products = (catalogData as CatalogItem[]) || []

  // Gather unique active categories dynamically from products list
  const categories = [
    { id: 'todos', name: 'Todos' },
    ...Array.from(new Set(products.map(p => p.category))).map(catName => ({
      id: catName.toLowerCase(),
      name: catName
    }))
  ]

  // Filter products by category AND search query
  const filteredProducts = products.filter(product => {
    const matchesCategory = activeCategory === 'todos' || product.category.toLowerCase() === activeCategory
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                          (product.description && product.description.toLowerCase().includes(searchQuery.toLowerCase()))
    return matchesCategory && matchesSearch
  })

  // Helper: Calculate promotional price
  const getPromoPrice = (originalPrice: number, promotions: CatalogPromotion[] | undefined) => {
    if (!promotions || promotions.length === 0) return null
    const promo = promotions[0]
    if (promo.discount_type === 'percentage') {
      return originalPrice - (originalPrice * promo.discount / 100)
    } else if (promo.discount_type === 'fixed') {
      return Math.max(0, originalPrice - promo.discount)
    }
    return null
  }

  // Get active variant details
  const activeVariant = detailData?.variants?.find(v => v.id === selectedVariantId) || detailData?.variants?.[0]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar cartCount={cartCount} />

      <main className="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12">
        {/* Title */}
        <div className="text-center space-y-3">
          <Typography variant="h1" className="text-3xl font-black text-primary">Nuestro Menú</Typography>
          <Typography variant="body" className="text-text-sub text-xs font-semibold">Filtra entre nuestras delicias horneadas y encuentra la tentación perfecta para tu día.</Typography>
        </div>

        {/* Toolbar: Search and categories */}
        <div className="bg-surface rounded-lg border border-border p-5 shadow-sm flex flex-col md:flex-row items-center gap-4 justify-between w-full">
          {/* Search bar */}
          <div className="relative w-full md:max-w-md">
            <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center text-text-sub/50 pointer-events-none">
              <FiSearch className="text-sm" />
            </span>
            <input
              type="text"
              placeholder="Buscar delicias por nombre, descripción..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-lg bg-background border border-border focus:border-primary focus:ring-1 focus:ring-stone-200 outline-none text-xs text-text-main transition-all duration-200"
            />
            {searchQuery && (
              <button 
                onClick={() => setSearchQuery('')} 
                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[10px] text-text-sub hover:text-primary font-bold"
              >
                Limpiar
              </button>
            )}
          </div>

          {/* Categories select pills */}
          <div className="flex flex-wrap items-center gap-1.5 w-full md:w-auto justify-end">
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => setActiveCategory(cat.id)}
                className={`px-4 py-2 rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all duration-200 active:scale-95 ${
                  activeCategory === cat.id
                    ? 'bg-primary text-primary-foreground shadow-md'
                    : 'bg-surface text-text-sub hover:bg-stone-100 dark:hover:bg-stone-800 border border-border/80'
                }`}
              >
                {cat.name}
              </button>
            ))}
          </div>
        </div>

        {/* PRODUCTS CATALOG LIST */}
        {isLoading ? (
          <Loading label="Cargando menú delicioso..." />
        ) : isError ? (
          <div className="py-16 text-center space-y-2">
            <span className="text-3xl">⚠️</span>
            <p className="text-primary font-bold text-lg">Ha ocurrido un problema</p>
            <p className="text-text-sub text-xs">No se pudo cargar el catálogo de productos.</p>
          </div>
        ) : filteredProducts.length === 0 ? (
          <EmptyState
            title="Sin resultados"
            description="No encontramos ningún pastel o delicia que coincida con tus filtros."
          />
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredProducts.map((product) => (
              <Card 
                key={product.id}
                className="overflow-hidden border border-border shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full bg-surface"
              >
                {/* Image wrapper */}
                <div className="h-56 bg-stone-50 relative flex items-center justify-center overflow-hidden">
                  {product.image ? (
                    <img 
                      src={product.image} 
                      alt={product.name} 
                      className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
                      onError={(e) => {
                        (e.target as HTMLImageElement).src = 'https://placehold.co/600x400?text=Dulce+Encanto'
                      }}
                    />
                  ) : (
                    <div className="text-6xl select-none">🧁</div>
                  )}
                </div>
                
                {/* Card Content */}
                <CardContent className="p-6 flex-grow flex flex-col justify-between space-y-4">
                  <div className="space-y-2">
                    <div className="flex items-center justify-between">
                      <Badge variant="info">{product.category}</Badge>
                      <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider">
                        desde <strong className="text-primary text-sm">${Number(product.min_price).toFixed(2)}</strong>
                      </span>
                    </div>
                    <h3 className="font-heading font-black text-lg text-primary group-hover:text-amber-800 transition-colors duration-200">
                      {product.name}
                    </h3>
                    <p className="text-text-sub text-xs leading-relaxed line-clamp-3 font-medium">
                      {product.description || 'Sin descripción disponible.'}
                    </p>
                  </div>
                  
                  <Button 
                    onClick={() => {
                      setSelectedProductId(product.id)
                      setSelectedVariantId(null)
                    }}
                    className="w-full text-[10px] uppercase font-bold tracking-wider"
                  >
                    Ver Opciones
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>

      {/* DETAIL MODAL */}
      {selectedProductId && (
        <Modal
          isOpen={!!selectedProductId}
          onClose={() => setSelectedProductId(null)}
          title={detailData?.name || 'Cargando postre...'}
          maxWidthClassName="max-w-4xl"
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            
            {/* LEFT: PHOTO GALLERY */}
            <div className="bg-stone-50 p-4 rounded-lg flex flex-col gap-4 border border-border">
              {isLoadingDetail ? (
                <div className="py-20 flex justify-center">
                  <svg className="animate-spin h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                </div>
              ) : !detailData?.gallery || detailData.gallery.length === 0 ? (
                <div className="py-16 text-center text-text-sub/50">
                  <span className="text-4xl block">🖼️</span>
                  <span className="text-[10px] font-bold uppercase tracking-wider mt-1 block">Sin imágenes cargadas</span>
                </div>
              ) : (
                <>
                  <div className="w-full aspect-square rounded-lg overflow-hidden bg-surface border border-border relative flex items-center justify-center">
                    <img 
                      src={detailData.gallery[activeImageIndex]?.image_url} 
                      alt="Product Detail"
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        (e.target as HTMLImageElement).src = 'https://placehold.co/600x600?text=Dulce+Encanto'
                      }}
                    />
                  </div>
                  {detailData.gallery.length > 1 && (
                    <div className="flex gap-2 overflow-x-auto py-1">
                      {detailData.gallery.map((img: any, index: number) => (
                        <button
                          key={img.id}
                          onClick={() => setActiveImageIndex(index)}
                          className={`w-14 h-14 rounded-lg overflow-hidden border-2 flex-shrink-0 transition-all ${
                            activeImageIndex === index 
                              ? 'border-primary scale-95 shadow-sm' 
                              : 'border-border hover:border-stone-400'
                          }`}
                        >
                          <img 
                            src={img.image_url} 
                            className="w-full h-full object-cover"
                            alt="thumb"
                          />
                        </button>
                      ))}
                    </div>
                  )}
                </>
              )}
            </div>

            {/* RIGHT: SELECTIONS & DETAILS */}
            <div className="space-y-6">
              {isLoadingDetail ? (
                <div className="space-y-4 py-8">
                  <div className="h-6 w-2/3 bg-stone-100 rounded animate-pulse"></div>
                  <div className="h-4 w-full bg-stone-100 rounded animate-pulse"></div>
                  <div className="h-4 w-5/6 bg-stone-100 rounded animate-pulse"></div>
                </div>
              ) : (
                <>
                  <div className="space-y-2">
                    <Badge variant="info">{detailData?.category}</Badge>
                    <Typography variant="body" className="text-text-sub font-semibold">
                      {detailData?.description || 'Sin descripción disponible.'}
                    </Typography>
                  </div>

                  {/* PROMOTION ALERTS */}
                  {detailData?.promotions && detailData.promotions.length > 0 && (
                    <div className="bg-rose-50 border border-rose-100 rounded-lg p-4 space-y-1">
                      <div className="flex items-center gap-1 text-[9px] font-extrabold text-rose-700 uppercase tracking-widest">
                        <FiTag className="text-xs text-rose-600" />
                        <span>Promoción Activa</span>
                      </div>
                      <h4 className="font-extrabold text-xs text-rose-800">{detailData.promotions[0].name}</h4>
                      <p className="text-[10px] text-rose-600 font-semibold">{detailData.promotions[0].description}</p>
                    </div>
                  )}

                  {/* SELECTION SIZE / PRICE */}
                  <div className="space-y-3">
                    <Typography variant="label" className="text-[10px] uppercase tracking-widest block">
                      Selecciona la Presentación / Tamaño:
                    </Typography>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                      {detailData?.variants?.map((v: any) => {
                        const isSel = selectedVariantId === v.id || (!selectedVariantId && detailData?.variants?.[0]?.id === v.id)
                        return (
                          <button
                            key={v.id}
                            type="button"
                            onClick={() => setSelectedVariantId(v.id)}
                            className={`p-3 rounded-lg border text-left transition-all ${
                              isSel 
                                ? 'border-primary bg-secondary/15 ring-1 ring-primary'
                                : 'border-border hover:border-primary bg-surface'
                            }`}
                          >
                            <div className="font-bold text-xs text-primary">{v.name}</div>
                          </button>
                        )
                      })}
                    </div>
                  </div>

                  {/* EXTRAS PIVOT DISPLAY */}
                  {detailData?.extras && detailData.extras.length > 0 && (
                    <div className="space-y-2">
                      <Typography variant="label" className="text-[10px] uppercase tracking-widest block">
                        Adicionales que puedes agregar:
                      </Typography>
                      <div className="flex flex-wrap gap-1.5">
                        {detailData.extras.map((ext: any) => (
                          <Badge key={ext.id} variant="neutral">
                            {ext.name} (+${Number(ext.price).toFixed(2)})
                          </Badge>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* PRICE & ADD ACTION */}
                  {activeVariant && (
                    <div className="border-t border-border pt-5 flex items-center justify-between gap-4">
                      <div className="space-y-0.5">
                        <span className="text-[9px] text-text-sub block font-bold uppercase tracking-widest">Precio Unitario</span>
                        <PriceTag
                          price={activeVariant.price}
                          promoPrice={getPromoPrice(activeVariant.price, detailData?.promotions)}
                          size="lg"
                        />
                      </div>
                      <Button
                        onClick={() => {
                          setCartCount(prev => prev + 1)
                          toast.success('Agregado al pedido.')
                          setSelectedProductId(null)
                        }}
                        className="text-[10px] uppercase tracking-wider"
                      >
                        Añadir al pedido
                      </Button>
                    </div>
                  )}
                </>
              )}
            </div>

          </div>
        </Modal>
      )}

      <Footer />
    </div>
  )
}
