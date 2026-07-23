import React from 'react'
import { Typography, Card, CardContent } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import contactBg from '@/assets/backgrounds/contact-background.webp'
import { 
  FiAward, 
  FiGrid, 
  FiGift, 
  FiCoffee, 
  FiHexagon, 
  FiCheckCircle, 
  FiStar, 
  FiHeart,
  FiSmile,
  FiTrendingUp,
  FiCheck
} from 'react-icons/fi'

export default function Servicios() {
  const serviceCategories = [
    {
      title: 'Repostería Creativa & Tortas',
      services: [
        { name: 'Tortas Personalizadas', desc: 'Diseños a medida adaptados a tu paleta de colores y estilo decorativo preferido.', icon: FiGift },
        { name: 'Tortas Temáticas', desc: 'Creaciones artísticas inspiradas en personajes, hobbies, profesiones y más.', icon: FiAward },
        { name: 'Cheesecakes Premium', desc: 'Deliciosos e increíbles pays de queso con base crujiente y toppings de frutos selectos.', icon: FiStar },
        { name: 'Cupcakes Decorados', desc: 'Pequeñas porciones llenas de detalles y crema suave para compartir individualmente.', icon: FiHexagon }
      ]
    },
    {
      title: 'Bocados & Panadería Artesanal',
      services: [
        { name: 'Brownies & Blondies', desc: 'Caldosos por dentro y con costra perfecta, elaborados con cacao de origen puro.', icon: FiHeart },
        { name: 'Galletas de Especialidad', desc: 'Galletas de mantequilla decoradas o galletas rellenas horneadas al día.', icon: FiCoffee },
        { name: 'Donas Horneadas', desc: 'Esponjosas y con glaseados artesanales libres de grasas saturadas.', icon: FiGrid },
        { name: 'Panes Artesanales', desc: 'Budines, panes de masa madre y panadería dulce clásica para desayunos gourmet.', icon: FiSmile }
      ]
    },
    {
      title: 'Bocaditos & Mesas para Eventos',
      services: [
        { name: 'Bocaditos Dulces', desc: 'Mini trufas, tartaletas de frutas, macarons franceses y shots de postres variados.', icon: FiTrendingUp },
        { name: 'Bocaditos Salados', desc: 'Hojaldres rellenos, mini quiches, empanadas gourmet y croquetas selectas.', icon: FiCheckCircle },
        { name: 'Mesas de Postres', desc: 'Montaje integral y decoración temática de barras dulces para impresionar a tus invitados.', icon: FiAward }
      ]
    }
  ]

  const cateringEvents = [
    { name: 'Bodas', desc: 'Creamos la torta de novios perfecta y una mesa de dulces elegante alineada al diseño de tu boda.' },
    { name: 'Bautizos y Primeras Comuniones', desc: 'Detalles dulces y pasteles en tonos pasteles para dar un aire angelical y tierno.' },
    { name: 'Cumpleaños y Quince Años', desc: 'Tortas monumentales e interactivas adaptadas al tema y temática de la fiesta.' },
    { name: 'Eventos Corporativos', desc: 'Bocaditos y postres con logo institucional o colores de marca para reuniones y lanzamientos.' }
  ]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow">
        {/* Banner */}
        <section 
          className="relative h-64 sm:h-80 bg-cover bg-center flex items-center justify-center"
          style={{ backgroundImage: `linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url(${contactBg})` }}
        >
          <div className="text-center space-y-3 px-4">
            <Typography variant="h1" className="text-3xl sm:text-4xl lg:text-5xl font-black text-white">
              Nuestros Servicios
            </Typography>
            <p className="text-stone-300 text-xs sm:text-sm max-w-xl mx-auto font-medium">
              Ofrecemos soluciones dulces completas para consentir a tus invitados en cualquier tipo de celebración.
            </p>
          </div>
        </section>

        {/* Services Categories */}
        <section className="py-16 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
          {serviceCategories.map((cat, idx) => (
            <div key={idx} className="space-y-8">
              <div className="border-b border-border pb-4">
                <Typography variant="h2" className="text-xl sm:text-2xl font-black text-primary">
                  {cat.title}
                </Typography>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {cat.services.map((s, sIdx) => {
                  const Icon = s.icon
                  return (
                    <Card key={sIdx} className="bg-surface border border-border shadow-sm hover:shadow-md transition-shadow">
                      <CardContent className="p-6 flex flex-col items-center text-center space-y-4">
                        <div className="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-primary text-xl">
                          <Icon />
                        </div>
                        <Typography variant="h3" className="text-sm font-black text-primary">
                          {s.name}
                        </Typography>
                        <p className="text-text-sub text-[11px] font-semibold leading-relaxed font-sans">
                          {s.desc}
                        </p>
                      </CardContent>
                    </Card>
                  )
                })}
              </div>
            </div>
          ))}
        </section>

        {/* Catering Section */}
        <section className="bg-surface py-16 border-t border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div className="text-center space-y-3 max-w-xl mx-auto">
              <Typography variant="h2" className="text-2xl font-black text-primary">Catering para Eventos Especiales</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold">
                Nos encargamos de endulzar tus momentos más memorables con máxima profesionalidad.
              </Typography>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {cateringEvents.map((evt, idx) => (
                <div key={idx} className="flex gap-3 items-start bg-background p-6 rounded-xl border border-border">
                  <div className="mt-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 p-1 rounded-full shrink-0">
                    <FiCheck className="text-sm stroke-[3]" />
                  </div>
                  <div className="space-y-1.5">
                    <h3 className="font-heading font-black text-sm text-primary">{evt.name}</h3>
                    <p className="text-text-sub text-xs leading-relaxed font-semibold font-sans">{evt.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  )
}
