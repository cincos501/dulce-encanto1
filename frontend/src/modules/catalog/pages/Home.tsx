import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Typography, Card, CardContent, Button, Badge } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import catalogService from '@/shared/services/catalogService'
import {
  FiStar,
  FiHeart,
  FiTrendingUp,
  FiAward,
  FiCoffee,
  FiSmile,
  FiMapPin,
  FiClock,
  FiChevronLeft,
  FiChevronRight,
  FiGift
} from 'react-icons/fi'
import { FaHeart, FaWhatsapp } from 'react-icons/fa'
import heroBg from '@/assets/backgrounds/hero-default.webp'
import productPlaceholder from '@/assets/placeholders/product-placeholder.webp'
import promoPlaceholder from '@/assets/placeholders/promotion-placeholder.webp'

interface CarouselItem {
  id: number;
  name: string;
  description: string;
  image?: string | null;
  image_url?: string | null;
  min_price?: number;
  promo_price?: number;
  discount_type?: string;
  discount?: number;
}

export default function Home() {
  const [favorites, setFavorites] = useState<number[]>(() => {
    const saved = localStorage.getItem('dulce_encanto_favorites')
    return saved ? JSON.parse(saved) : []
  })

  const [carouselIndex, setCarouselIndex] = useState(0)

  useEffect(() => {
    localStorage.setItem('dulce_encanto_favorites', JSON.stringify(favorites))
  }, [favorites])

  const toggleFavorite = (id: number) => {
    setFavorites(prev =>
      prev.includes(id) ? prev.filter(fId => fId !== id) : [...prev, id]
    )
  }

  // React Query: Get public promotions
  const { data: promotionsData } = useQuery({
    queryKey: ['public-promotions-home'],
    queryFn: async () => {
      const response = await catalogService.getPromotions()
      return response.data?.data || []
    }
  })

  // React Query: Get public catalog
  const { data: catalogData } = useQuery({
    queryKey: ['public-catalog-home'],
    queryFn: async () => {
      const response = await catalogService.getCatalog()
      return response.data?.data || []
    }
  })

  const promotions = (promotionsData as any[]) || []
  const catalog = (catalogData as any[]) || []

  const showPromotions = promotions.length > 0
  const carouselItems: CarouselItem[] = showPromotions ? promotions : catalog.slice(0, 6)

  // Carousel handlers
  const nextSlide = () => {
    if (carouselItems.length === 0) return
    setCarouselIndex(prev => (prev + 1) % carouselItems.length)
  }

  const prevSlide = () => {
    if (carouselItems.length === 0) return
    setCarouselIndex(prev => (prev - 1 + carouselItems.length) % carouselItems.length)
  }

  const chooseUs = [
    { title: 'Ingredientes 100% Frescos', desc: 'Frutas frescas del día, chocolates belgas selectos y mantequilla premium pura.', icon: FiStar },
    { title: 'Reposteros Expertos', desc: 'Decoraciones artísticas de alta costura y sabores balanceados.', icon: FiAward },
    { title: 'Detalles Personalizados', desc: 'Elaboramos y ajustamos las recetas según tus preferencias y restricciones.', icon: FiHeart },
    { title: 'Higiene y Calidad Superior', desc: 'Estrictos protocolos de inocuidad y empaques premium seguros.', icon: FiSmile }
  ]

  const categoriesGrid = [
    { name: 'Tortas Personalizadas', desc: 'Diseños decorados con fondant o buttercream.', icon: FiGift },
    { name: 'Postres Individuales', desc: 'Cheesecakes, brownies, cookies y tartaletas.', icon: FiCoffee },
    { name: 'Bocaditos Gourmet', desc: 'Bocaditos dulces y salados para mesas de dulces.', icon: FiTrendingUp },
    { name: 'Panes Artesanales', desc: 'Panes dulces tradicionales recién horneados.', icon: FiSmile }
  ]

  const occasions = [
    { title: 'Cumpleaños', desc: 'Tortas decoradas temáticas y cupcakes personalizados para celebrar a lo grande.' },
    { title: 'Bodas', desc: 'Elegancia clásica y sabores sofisticados con montajes a medida.' },
    { title: 'Bautizos', desc: 'Postres tiernos y coloridos para dar un toque angelical.' },
    { title: 'Quince Años', desc: 'Tortas monumentales y cascadas de chocolate espectaculares.' },
    { title: 'Eventos Empresariales', desc: 'Bocaditos dulces y salados con logotipos y colores corporativos.' }
  ]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow">
        {/* HERO SECTION */}
        <section
          className="relative overflow-hidden py-24 lg:py-32 bg-cover bg-center"
          style={{ backgroundImage: `url(${heroBg})` }}
        >
          {/* Theme-responsive overlay gradient */}
          <div className="absolute inset-0 bg-gradient-to-r from-[#FAF6F0]/98 via-[#FAF6F0]/90 to-transparent dark:from-[#181615]/98 dark:via-[#181615]/90 dark:to-transparent pointer-events-none"></div>

          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div className="max-w-2xl space-y-6 text-left">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] uppercase font-bold tracking-widest bg-secondary/15 text-primary border border-secondary/30 select-none">
                <FiStar className="text-xs text-amber-500" />
                <span>Repostería Premium Artesanal</span>
              </span>
              <Typography variant="h1" className="text-4xl sm:text-5xl lg:text-6xl font-black text-primary leading-tight tracking-tight">
                Horneamos felicidad <br />
                <span className="text-stone-700 dark:text-stone-300">
                  para cada ocasión
                </span>
              </Typography>
              <Typography variant="body" className="text-text-sub text-xs sm:text-sm leading-relaxed font-semibold max-w-xl">
                En <strong>Dulce Encanto</strong>, creemos en la magia de los detalles. Cada ingrediente es seleccionado cuidadosamente para crear recetas únicas que conquistan el paladar.
              </Typography>
              <div className="flex flex-col sm:flex-row items-center gap-4 pt-4">
                <Link
                  to="/menu"
                  className="w-full sm:w-auto text-center bg-primary text-primary-foreground px-8 py-3.5 rounded-lg font-bold hover:bg-stone-850 dark:hover:bg-stone-155 transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
                >
                  Ver Catálogo
                </Link>
                <a
                  href="https://wa.me/59170012345"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3.5 rounded-lg font-bold transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
                >
                  <FaWhatsapp className="text-base" />
                  <span>Contactar por WhatsApp</span>
                </a>
              </div>
            </div>
          </div>
        </section>

        {/* DYNAMIC CAROUSEL */}
        {carouselItems.length > 0 && (
          <section className="py-20 bg-surface border-y border-border">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
              <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div className="space-y-3">
                  <span className="inline-block text-[10px] font-bold uppercase tracking-widest bg-secondary/15 text-primary px-3 py-1 rounded-lg border border-secondary/30">
                    {showPromotions ? 'Ofertas del Mes' : 'Nuestras Recomendaciones'}
                  </span>
                  <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">
                    {showPromotions ? 'Campañas Promocionales' : 'Favoritos de la Temporada'}
                  </Typography>
                </div>
                {/* Navigation arrows */}
                <div className="flex gap-2">
                  <button
                    onClick={prevSlide}
                    className="p-2 border border-border rounded-lg bg-background hover:bg-stone-100 text-text-sub hover:text-primary transition-colors cursor-pointer"
                  >
                    <FiChevronLeft className="text-lg" />
                  </button>
                  <button
                    onClick={nextSlide}
                    className="p-2 border border-border rounded-lg bg-background hover:bg-stone-100 text-text-sub hover:text-primary transition-colors cursor-pointer"
                  >
                    <FiChevronRight className="text-lg" />
                  </button>
                </div>
              </div>

              {/* Slider Viewport */}
              <div className="relative overflow-hidden rounded-xl border border-border bg-background p-6 md:p-8">
                <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                  {/* Image Column */}
                  <div className="md:col-span-5 relative h-64 md:h-80 w-full rounded-lg overflow-hidden border border-border shadow-inner">
                    <img
                      src={carouselItems[carouselIndex].image || carouselItems[carouselIndex].image_url || (showPromotions ? promoPlaceholder : productPlaceholder)}
                      alt={carouselItems[carouselIndex].name}
                      className="w-full h-full object-cover"
                    />
                    {/* Heart button */}
                    <button
                      onClick={() => toggleFavorite(carouselItems[carouselIndex].id)}
                      className="absolute top-4 right-4 p-2.5 rounded-full bg-white/80 dark:bg-stone-900/80 backdrop-blur-sm border border-border/40 shadow text-rose-500 hover:scale-110 active:scale-95 transition-all cursor-pointer"
                    >
                      {favorites.includes(carouselItems[carouselIndex].id) ? (
                        <FaHeart className="text-lg animate-fade-in" />
                      ) : (
                        <FiHeart className="text-lg" />
                      )}
                    </button>
                  </div>

                  {/* Content Column */}
                  <div className="md:col-span-7 space-y-6">
                    <div className="space-y-2">
                      <h3 className="font-heading font-black text-xl sm:text-2xl text-primary leading-tight">
                        {carouselItems[carouselIndex].name}
                      </h3>
                      {showPromotions && (
                        <div className="inline-block">
                          <Badge variant="error" className="font-bold text-[10px] tracking-wide py-1 px-2.5">
                            {carouselItems[carouselIndex].discount_type === 'percentage'
                              ? `${Number(carouselItems[carouselIndex].discount).toFixed(0)}% DESCUENTO`
                              : `Bs. ${Number(carouselItems[carouselIndex].discount).toFixed(0)} DESCUENTO`}
                          </Badge>
                        </div>
                      )}
                      <p className="text-text-sub text-xs sm:text-sm font-semibold font-sans leading-relaxed">
                        {carouselItems[carouselIndex].description}
                      </p>
                    </div>

                    {!showPromotions && (
                      <div className="text-primary font-mono font-bold text-lg">
                        Bs. {Number(carouselItems[carouselIndex].min_price || 0).toFixed(2)}
                      </div>
                    )}

                    <div className="pt-2 flex flex-wrap gap-4">
                      {showPromotions ? (
                        <Link
                          to="/promociones"
                          className="bg-primary text-primary-foreground px-6 py-3 rounded-lg font-bold text-xs uppercase tracking-wider font-sans hover:bg-stone-850 transition-colors"
                        >
                          Ver Detalles de Promoción
                        </Link>
                      ) : (
                        <Link
                          to="/menu"
                          className="bg-primary text-primary-foreground px-6 py-3 rounded-lg font-bold text-xs uppercase tracking-wider font-sans hover:bg-stone-850 transition-colors"
                        >
                          Ver en el Menú
                        </Link>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        )}

        {/* ¿QUÉ ENCONTRARÁS? */}
        <section className="py-20 bg-background">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div className="text-center space-y-3 max-w-xl mx-auto">
              <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">¿Qué encontrarás?</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold">
                Descubre las diferentes clasificaciones de delicias que horneamos a diario.
              </Typography>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {categoriesGrid.map((cat, idx) => {
                const Icon = cat.icon
                return (
                  <Card key={idx} className="bg-surface border border-border shadow-sm">
                    <CardContent className="p-6 text-center space-y-4 flex flex-col items-center">
                      <div className="w-10 h-10 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-primary text-lg">
                        <Icon />
                      </div>
                      <Typography variant="h3" className="text-sm font-black text-primary">{cat.name}</Typography>
                      <p className="text-text-sub text-[11px] font-semibold font-sans leading-relaxed">{cat.desc}</p>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>
        </section>

        {/* ¿POR QUÉ ELEGIR DULCE ENCANTO? */}
        <section className="bg-surface py-20 border-y border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div className="text-center space-y-3 max-w-xl mx-auto">
              <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">¿Por qué elegir Dulce Encanto?</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold">
                Nos apasiona entregar solo lo mejor en cada receta y servicio.
              </Typography>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
              {chooseUs.map((c, idx) => {
                const Icon = c.icon
                return (
                  <div key={idx} className="space-y-3 flex flex-col items-center text-center">
                    <div className="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-primary text-xl shadow-sm border border-secondary/20">
                      <Icon />
                    </div>
                    <h4 className="font-heading font-black text-sm text-primary">{c.title}</h4>
                    <p className="text-text-sub text-[11px] leading-relaxed px-2 font-semibold font-sans">{c.desc}</p>
                  </div>
                )
              })}
            </div>
          </div>
        </section>

        {/* PRODUCTOS DESTACADOS */}
        {catalog.length > 0 && (
          <section className="py-20 bg-background">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
              <div className="text-center space-y-3 max-w-xl mx-auto">
                <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">Productos Destacados</Typography>
                <Typography variant="body" className="text-text-sub text-xs font-semibold">
                  Nuestras creaciones más solicitadas y valoradas por nuestros clientes.
                </Typography>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {catalog.slice(0, 4).map((prod) => (
                  <Card key={prod.id} className="bg-surface border border-border shadow-sm overflow-hidden flex flex-col justify-between">
                    <div className="relative h-44 bg-stone-100 overflow-hidden">
                      <img
                        src={prod.image || productPlaceholder}
                        alt={prod.name}
                        className="w-full h-full object-cover"
                      />
                      <button
                        onClick={() => toggleFavorite(prod.id)}
                        className="absolute top-3 right-3 p-2 rounded-full bg-white/80 dark:bg-stone-900/80 backdrop-blur-sm border border-border/40 shadow text-rose-500 hover:scale-110 active:scale-95 transition-all cursor-pointer"
                      >
                        {favorites.includes(prod.id) ? (
                          <FaHeart className="text-sm" />
                        ) : (
                          <FiHeart className="text-sm" />
                        )}
                      </button>
                    </div>
                    <CardContent className="p-5 flex-grow flex flex-col justify-between space-y-3">
                      <div className="space-y-1">
                        <span className="text-[9px] uppercase tracking-wider font-bold text-text-sub block">{prod.category}</span>
                        <Typography variant="h3" className="text-xs font-black text-primary leading-tight">{prod.name}</Typography>
                        <p className="text-text-sub text-[10px] line-clamp-2 leading-relaxed font-sans font-semibold">{prod.description}</p>
                      </div>
                      <div className="flex items-center justify-between pt-2 border-t border-border">
                        <span className="font-mono font-bold text-xs text-primary">
                          {prod.promo_price ? (
                            <span className="flex items-center gap-1.5">
                              <span className="line-through text-text-sub text-[10px]">Bs. {Number(prod.min_price).toFixed(2)}</span>
                              <span className="text-rose-600 dark:text-rose-400">Bs. {Number(prod.promo_price).toFixed(2)}</span>
                            </span>
                          ) : (
                            <span>Bs. {Number(prod.min_price).toFixed(2)}</span>
                          )}
                        </span>
                        <Link to="/menu" className="text-[10px] font-black text-primary uppercase tracking-wider font-sans border-b border-primary/40 pb-0.5 hover:text-primary/80">
                          Comprar
                        </Link>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          </section>
        )}

        {/* CELEBRAMOS CONTIGO */}
        <section className="bg-surface py-20 border-t border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div className="text-center space-y-3 max-w-xl mx-auto">
              <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">Celebramos contigo</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold font-sans">
                Acompañamos tus fechas especiales y momentos significativos con pastelería de primer nivel.
              </Typography>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
              {occasions.map((o, idx) => (
                <Card key={idx} className="bg-background border border-border shadow-sm">
                  <CardContent className="p-5 space-y-2 text-center">
                    <h4 className="font-heading font-black text-xs sm:text-sm text-primary uppercase tracking-wide">{o.title}</h4>
                    <p className="text-text-sub text-[10px] leading-relaxed font-semibold font-sans">{o.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        {/* CONTACTO RÁPIDO */}
        <section className="py-20 bg-background relative overflow-hidden">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <Card className="bg-surface border border-border/80 shadow-md p-6 sm:p-10 rounded-2xl">
              <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                {/* Info */}
                <div className="md:col-span-7 space-y-6">
                  <div className="space-y-2">
                    <Typography variant="h2" className="text-2xl font-black text-primary leading-none">Contacto Rápido</Typography>
                    <p className="text-text-sub text-xs font-semibold leading-relaxed">¿Quieres visitarnos o hacer un pedido urgente? Estamos a un clic de distancia.</p>
                  </div>

                  <div className="space-y-3 text-xs leading-none">
                    <div className="flex items-center gap-2.5">
                      <FiMapPin className="text-primary text-sm shrink-0" />
                      <span className="text-text-sub font-semibold">Equipetrol Calle 8 Este, Santa Cruz de la Sierra</span>
                    </div>
                    <div className="flex items-center gap-2.5">
                      <FiClock className="text-primary text-sm shrink-0" />
                      <span className="text-text-sub font-semibold">Mar a Dom: 9:00 AM - 8:00 PM (Lunes Cerrado)</span>
                    </div>
                    <div className="flex items-center gap-2.5">
                      <FaWhatsapp className="text-emerald-600 text-sm shrink-0" />
                      <span className="text-text-sub font-semibold">+591 700 12345 (Atención Inmediata)</span>
                    </div>
                  </div>
                </div>

                {/* Actions */}
                <div className="md:col-span-5 flex flex-col gap-3">
                  <a
                    href="https://wa.me/59170012345"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-lg text-xs uppercase tracking-wider font-sans shadow-md hover:shadow-lg transition-all"
                  >
                    <FaWhatsapp className="text-base" />
                    <span>Escríbenos por WhatsApp</span>
                  </a>
                  <a
                    href="https://goo.gl/maps/example"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="w-full inline-flex items-center justify-center gap-2 bg-background hover:bg-stone-50 border border-border text-primary font-bold py-3.5 rounded-lg text-xs uppercase tracking-wider font-sans shadow-sm hover:shadow transition-all"
                  >
                    <FiMapPin className="text-sm" />
                    <span>Ver en Google Maps</span>
                  </a>
                </div>
              </div>
            </Card>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  )
}
