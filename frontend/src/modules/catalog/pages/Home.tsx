import React from 'react'
import { Link } from 'react-router-dom'
import { Typography, Button, Card, CardContent } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiStar, FiHeart, FiTrendingUp, FiAward, FiGift, FiCoffee } from 'react-icons/fi'

export default function Home() {
  const benefits = [
    { title: 'Ingredientes Premium', desc: 'Frutas frescas de temporada, chocolates belgas selectos y mantequilla normanda.', icon: FiStar },
    { title: 'Hecho a Mano con Amor', desc: 'Cada postre es elaborado de manera artesanal por reposteros dedicados.', icon: FiHeart },
    { title: 'Calidad Consistente', desc: 'Recetas exclusivas que garantizan una experiencia inolvidable en cada bocado.', icon: FiTrendingUp }
  ]

  const featuredCategories = [
    { name: 'Tortas & Pasteles', icon: FiAward, desc: 'Elegancia y sabor para celebraciones memorables.' },
    { name: 'Muffins & Cupcakes', icon: FiStar, desc: 'Pequeñas porciones de felicidad horneada.' },
    { name: 'Budines & Tartas', icon: FiCoffee, desc: 'Clásicos artesanales para compartir por la tarde.' }
  ]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow">
        {/* HERO SECTION */}
        <section className="relative overflow-hidden py-16 lg:py-24 bg-background">
          <div className="absolute top-0 left-1/4 w-96 h-96 bg-amber-100/20 rounded-full filter blur-3xl opacity-30 animate-pulse"></div>
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
              
              <div className="lg:col-span-7 space-y-6 text-center lg:text-left">
                <span className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-[10px] uppercase font-bold tracking-widest bg-secondary/15 text-primary border border-secondary/30 select-none">
                  <FiStar className="text-xs text-amber-500" />
                  <span>Repostería Premium Artesanal</span>
                </span>
                <Typography variant="h1" className="text-4xl sm:text-5xl lg:text-6xl font-black text-primary leading-tight tracking-tight">
                  Horneamos felicidad <br className="hidden sm:inline" />
                  <span className="bg-gradient-to-r from-primary via-stone-800 to-stone-605 bg-clip-text text-transparent">
                    para cada ocasión
                  </span>
                </Typography>
                <Typography variant="body" className="text-text-sub text-xs max-w-xl mx-auto lg:mx-0 leading-relaxed font-semibold">
                  En <strong>Dulce Encanto</strong>, creemos en la magia de los detalles. Cada ingrediente es seleccionado cuidadosamente para crear recetas únicas que conquistan el paladar.
                </Typography>
                <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                  <Link 
                    to="/menu" 
                    className="w-full sm:w-auto text-center bg-primary text-primary-foreground px-8 py-3.5 rounded-lg font-bold hover:bg-stone-850 dark:hover:bg-stone-155 transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
                  >
                    Ver Nuestro Menú
                  </Link>
                  <Link 
                    to="/contacto" 
                    className="w-full sm:w-auto text-center bg-surface text-text-main border border-border px-8 py-3.5 rounded-lg font-bold hover:bg-stone-100 dark:hover:bg-stone-800 transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
                  >
                    Contáctanos
                  </Link>
                </div>
              </div>

              <div className="lg:col-span-5 flex justify-center">
                <div className="relative">
                  <div className="w-80 h-80 sm:w-96 sm:h-96 rounded-full bg-gradient-to-tr from-secondary/10 to-stone-200/20 absolute -inset-3 blur-md opacity-70 animate-spin-slow"></div>
                  <div className="w-80 h-80 sm:w-96 sm:h-96 rounded-lg bg-surface shadow-xl relative flex flex-col items-center justify-center border border-border p-8 text-center overflow-hidden">
                    <FiGift className="text-6xl mb-4 text-amber-500 stroke-[1.2] shrink-0" />
                    <h3 className="font-heading font-black text-xl text-primary">Red Velvet Supreme</h3>
                    <p className="text-amber-600 dark:text-amber-450 font-bold mt-1 text-xs uppercase tracking-widest font-sans">Especialidad de la Casa</p>
                    <p className="text-text-sub text-xs mt-2 px-6 font-medium font-sans">Esponjosas capas de cacao selecto y nuestra inconfundible crema suave de queso mascarpone.</p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </section>

        {/* VALUE PROPOSITIONS */}
        <section className="bg-surface py-16 border-y border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
              {benefits.map((b, idx) => {
                const Icon = b.icon
                return (
                  <div key={idx} className="space-y-3 flex flex-col items-center">
                    <div className="w-12 h-12 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-primary text-xl">
                      <Icon />
                    </div>
                    <h4 className="font-heading font-black text-base text-primary">{b.title}</h4>
                    <p className="text-text-sub text-xs leading-relaxed px-4 font-semibold font-sans">{b.desc}</p>
                  </div>
                )
              })}
            </div>
          </div>
        </section>

        {/* FEATURED CATEGORIES */}
        <section className="py-20 bg-background/30">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center space-y-3 max-w-xl mx-auto mb-16">
              <Typography variant="h2" className="text-3xl font-black text-primary">Nuestras Especialidades</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold">Descubre las diferentes clasificaciones de delicias que horneamos a diario.</Typography>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {featuredCategories.map((cat, idx) => {
                const Icon = cat.icon
                return (
                  <Card key={idx} className="bg-surface border border-border shadow-sm hover:shadow-lg transition-all duration-300">
                    <CardContent className="p-8 text-center space-y-4 flex flex-col items-center">
                      <Icon className="text-5xl text-primary stroke-[1.2] shrink-0" />
                      <Typography variant="h3" className="text-lg font-black text-primary">{cat.name}</Typography>
                      <p className="text-text-sub text-xs font-semibold font-sans leading-relaxed">{cat.desc}</p>
                      <div className="pt-2">
                        <Link 
                          to="/menu" 
                          className="text-xs font-bold text-primary hover:text-primary/80 transition-colors uppercase tracking-wider font-sans border-b border-primary/40 pb-0.5"
                        >
                          Ver Selección
                        </Link>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>
        </section>

        {/* CALL TO ACTION */}
        <section className="bg-surface border-t border-border py-20 text-center relative overflow-hidden">
          <div className="absolute inset-0 bg-secondary/5 opacity-50 pointer-events-none select-none"></div>
          <div className="max-w-2xl mx-auto px-4 relative space-y-6">
            <Typography variant="h2" className="text-3xl font-black text-primary">
              ¿Listo para dar un toque dulce a tu evento?
            </Typography>
            <Typography variant="body" className="text-text-sub text-xs font-semibold leading-relaxed">
              Realizamos presupuestos personalizados para bodas, cumpleaños y eventos corporativos. Escríbenos y diseñemos juntos el postre de tus sueños.
            </Typography>
            <div className="pt-4 flex justify-center gap-4">
              <Link 
                to="/contacto" 
                className="bg-primary text-primary-foreground px-8 py-3.5 rounded-lg font-bold hover:bg-stone-850 dark:hover:bg-stone-155 transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
              >
                Ponte en Contacto
              </Link>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  )
}
