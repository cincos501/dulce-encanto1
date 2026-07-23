import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import catalogService from '@/shared/services/catalogService'
import { Typography, Card, CardContent, Badge, EmptyState, Loading } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiPercent, FiCalendar, FiArrowRight, FiX, FiCheck } from 'react-icons/fi'
import promoPlaceholder from '@/assets/placeholders/promotion-placeholder.webp'
import productPlaceholder from '@/assets/placeholders/product-placeholder.webp'

interface VariantDiscount {
  id: number;
  name: string;
  price: number;
  promo_price: number;
  discount_text: string;
}

interface PromoProduct {
  id: number;
  name: string;
  description: string;
  category: string;
  image: string | null;
  variants_with_discount: VariantDiscount[];
}

interface PublicPromotion {
  id: number;
  name: string;
  description: string;
  discount_type: 'percentage' | 'fixed';
  discount: number;
  start_date: string;
  end_date: string;
  image_url: string | null;
  products: PromoProduct[];
}

export default function Promotions() {
  const [selectedPromoId, setSelectedPromoId] = useState<number | null>(null)

  // React Query: Get public promotions
  const { data: promotionsResponse, isLoading } = useQuery({
    queryKey: ['public-promotions'],
    queryFn: async () => {
      const response = await catalogService.getPromotions()
      return response.data?.data || []
    },
    staleTime: 5000
  })

  const promotionsList = (promotionsResponse as PublicPromotion[]) || []
  const selectedPromo = promotionsList.find(p => p.id === selectedPromoId)

  // Date formatter
  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('es-ES', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12 animate-fade-in">
        {/* Title */}
        <div className="text-center space-y-3">
          <Typography variant="h1" className="text-3xl font-black text-primary">Campañas y Promociones</Typography>
          <Typography variant="body" className="text-text-sub text-xs font-semibold">
            Descubre los mejores descuentos de temporada y date un dulce gusto ahorrando más.
          </Typography>
        </div>

        {isLoading ? (
          <Loading label="Buscando promociones deliciosas..." />
        ) : promotionsList.length === 0 ? (
          <EmptyState
            title="Sin promociones activas"
            description="Actualmente no tenemos campañas activas. Vuelve pronto para descubrir nuevas sorpresas dulces."
          />
        ) : (
          <div className="space-y-16">
            {/* Promotions Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {promotionsList.map((promo) => (
                <Card 
                  key={promo.id}
                  className={`bg-surface border shadow-sm rounded-lg overflow-hidden flex flex-col justify-between transition-all duration-200 ${
                    selectedPromoId === promo.id ? 'border-primary ring-2 ring-primary/20 scale-[1.02]' : 'border-border hover:shadow-md'
                  }`}
                >
                  <div className="relative h-48 bg-stone-100 dark:bg-stone-850 overflow-hidden">
                    <img 
                      src={promo.image_url || promoPlaceholder} 
                      alt={promo.name}
                      className="w-full h-full object-cover"
                    />
                    <div className="absolute top-3 right-3">
                      <Badge variant="error" className="font-bold text-xs shadow-md py-1 px-2.5">
                        {promo.discount_type === 'percentage' 
                          ? `${Number(promo.discount).toFixed(0)}% OFF` 
                          : `Bs. ${Number(promo.discount).toFixed(0)} OFF`}
                      </Badge>
                    </div>
                  </div>

                  <CardContent className="p-6 flex-grow flex flex-col justify-between space-y-4">
                    <div className="space-y-2">
                      <Typography variant="h3" className="text-base font-black text-primary leading-tight">
                        {promo.name}
                      </Typography>
                      <p className="text-text-sub text-xs font-semibold font-sans leading-relaxed line-clamp-3">
                        {promo.description}
                      </p>
                    </div>

                    <div className="space-y-3 pt-2">
                      <div className="flex items-center gap-2 text-[10px] text-text-sub font-bold font-sans">
                        <FiCalendar className="text-xs shrink-0" />
                        <span>Vigencia: {formatDate(promo.start_date)} - {formatDate(promo.end_date)}</span>
                      </div>

                      <button
                        onClick={() => setSelectedPromoId(selectedPromoId === promo.id ? null : promo.id)}
                        className={`w-full flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-lg text-xs font-bold transition-all duration-200 cursor-pointer ${
                          selectedPromoId === promo.id
                            ? 'bg-primary text-primary-foreground hover:bg-stone-800'
                            : 'bg-secondary/15 hover:bg-secondary/35 text-primary'
                        }`}
                      >
                        <span>{selectedPromoId === promo.id ? 'Ocultar productos' : 'Ver productos'}</span>
                        <FiArrowRight className={`text-xs transition-transform duration-200 ${selectedPromoId === promo.id ? 'rotate-90' : ''}`} />
                      </button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {/* Associated Products Section */}
            {selectedPromo && (
              <div className="space-y-6 bg-surface border border-border p-6 sm:p-8 rounded-xl animate-slide-down">
                <div className="flex justify-between items-start gap-4 pb-4 border-b border-border">
                  <div className="space-y-1">
                    <Typography variant="h2" className="text-lg font-black text-primary">
                      Productos en promoción: {selectedPromo.name}
                    </Typography>
                    <p className="text-text-sub text-[11px] font-semibold">
                      Los siguientes productos cuentan con descuentos exclusivos en las presentaciones indicadas.
                    </p>
                  </div>
                  <button 
                    onClick={() => setSelectedPromoId(null)}
                    className="p-1.5 hover:bg-stone-100 dark:hover:bg-stone-800 text-text-sub hover:text-primary rounded-lg transition-colors cursor-pointer"
                  >
                    <FiX className="text-lg" />
                  </button>
                </div>

                {selectedPromo.products.length === 0 ? (
                  <p className="text-center text-xs text-text-sub font-semibold font-sans py-8 italic">
                    Esta promoción no tiene productos asociados activos actualmente.
                  </p>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {selectedPromo.products.map((prod) => (
                      <Card key={prod.id} className="bg-background border border-border/80 shadow-sm overflow-hidden flex flex-col justify-between">
                        <div className="relative h-44 bg-stone-150 overflow-hidden">
                          <img 
                            src={prod.image || productPlaceholder} 
                            alt={prod.name}
                            className="w-full h-full object-cover"
                          />
                          <div className="absolute top-2.5 left-2.5">
                            <span className="bg-white/80 dark:bg-stone-900/80 backdrop-blur-sm text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded text-primary border border-border/40">
                              {prod.category}
                            </span>
                          </div>
                        </div>

                        <CardContent className="p-5 flex-grow flex flex-col justify-between space-y-4">
                          <div className="space-y-1">
                            <Typography variant="h3" className="text-sm font-black text-primary leading-tight">
                              {prod.name}
                            </Typography>
                            <p className="text-text-sub text-[10px] leading-relaxed font-sans font-semibold">
                              {prod.description}
                            </p>
                          </div>

                          {/* Discounted Variants */}
                          <div className="space-y-2 pt-2 border-t border-border">
                            <span className="text-[9px] uppercase tracking-wider font-bold text-text-sub">Presentaciones con descuento:</span>
                            <div className="space-y-1.5">
                              {prod.variants_with_discount.map((v) => (
                                <div key={v.id} className="flex items-center justify-between bg-surface border border-border/60 p-2.5 rounded-lg text-xs leading-none">
                                  <div className="flex items-center gap-1.5">
                                    <div className="bg-rose-500/10 text-rose-600 dark:text-rose-400 p-0.5 rounded-full shrink-0">
                                      <FiCheck className="text-[10px] stroke-[3]" />
                                    </div>
                                    <span className="font-bold text-text-main text-[10px]">{v.name}</span>
                                  </div>
                                  <div className="text-right flex items-center gap-2">
                                    <span className="text-text-sub line-through text-[9px] font-medium">Bs. {v.price.toFixed(2)}</span>
                                    <span className="text-rose-600 dark:text-rose-400 font-bold font-mono text-[11px]">Bs. {v.promo_price.toFixed(2)}</span>
                                  </div>
                                </div>
                              ))}
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </main>

      <Footer />
    </div>
  )
}
