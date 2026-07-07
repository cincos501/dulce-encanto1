import React from 'react'
import { useQuery } from '@tanstack/react-query'
import catalogService from '@/shared/services/catalogService'
import { Typography, Card, CardContent, Badge, EmptyState, Loading, Divider } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiPercent } from 'react-icons/fi'

interface PromoCampaign {
  id: number;
  name: string;
  description: string;
  discount: number;
  discount_type: 'percentage' | 'fixed';
  start_date: string;
  end_date: string;
  is_active: boolean;
}

export default function Promotions() {
  // React Query: Get catalog items to display or extract promotions
  const { data: catalogData, isLoading } = useQuery({
    queryKey: ['public-catalog'],
    staleTime: 5000
  })

  // We extract promotions from catalog items since there is no separate public promotions endpoint
  const products = (catalogData as any[]) || []
  const promotionsMap: Record<number, PromoCampaign> = {}

  products.forEach(p => {
    if (p.promotions && p.promotions.length > 0) {
      p.promotions.forEach((promo: PromoCampaign) => {
        promotionsMap[promo.id] = promo
      })
    }
  })

  const promotionsList = Object.values(promotionsMap)

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12">
        {/* Title */}
        <div className="text-center space-y-3">
          <Typography variant="h1" className="text-3xl font-black text-primary">Nuestras Promociones</Typography>
          <Typography variant="body" className="text-text-sub text-xs font-semibold">
            Descubre los descuentos y campañas exclusivas de temporada que tenemos preparadas para ti.
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
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {promotionsList.map((promo) => (
              <Card 
                key={promo.id}
                className="bg-surface border border-border shadow-sm rounded-lg overflow-hidden flex flex-col justify-between"
              >
                <CardContent className="p-8 space-y-4">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-rose-600 dark:text-rose-400 text-lg">
                      <FiPercent />
                    </div>
                    <div>
                      <Typography variant="h3" className="text-lg font-black text-primary">{promo.name}</Typography>
                      <Badge variant="success" className="text-[9px] font-bold">Campaña Activa</Badge>
                    </div>
                  </div>
                  <p className="text-text-sub text-xs leading-relaxed font-sans font-semibold">
                    {promo.description}
                  </p>
                  <Divider />
                  <div className="flex items-center justify-between text-xs text-text-sub font-sans font-bold">
                    <span>Descuento aplicado:</span>
                    <span className="text-rose-600 dark:text-rose-400 font-black text-sm">
                      {promo.discount_type === 'percentage' ? `${promo.discount}%` : `$${promo.discount}`}
                    </span>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>

      <Footer />
    </div>
  )
}
