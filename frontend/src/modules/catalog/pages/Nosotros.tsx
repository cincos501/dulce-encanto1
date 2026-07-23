import React from 'react'
import { Typography, Card, CardContent } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import contactBg from '@/assets/backgrounds/contact-background.webp'
import { FiHeart, FiStar, FiAward, FiSmile } from 'react-icons/fi'

export default function Nosotros() {
  const highlights = [
    {
      title: 'Ingredientes Selectos',
      desc: 'Utilizamos frutas frescas de temporada, chocolates con altos porcentajes de cacao selecto y mantequilla premium pura. No comprometemos la calidad de nuestra materia prima.',
      icon: FiStar
    },
    {
      title: 'Elaboración a Mano',
      desc: 'Cada postre es horneado y decorado de forma artesanal por nuestro equipo de reposteros, dedicando el tiempo y cuidado necesario a cada detalle para un acabado impecable.',
      icon: FiHeart
    },
    {
      title: 'Atención Personalizada',
      desc: 'Nos tomamos el tiempo de escuchar tus ideas y gustos para adaptar nuestras recetas y decoraciones, logrando un postre que represente a la perfección tus celebraciones.',
      icon: FiSmile
    },
    {
      title: 'Experiencia en Eventos',
      desc: 'Llevamos años acompañando cumpleaños, bodas y eventos empresariales con nuestras exclusivas mesas de dulces, diseñando experiencias de sabor memorables.',
      icon: FiAward
    }
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
              Nuestra Historia
            </Typography>
            <p className="text-stone-300 text-xs sm:text-sm max-w-xl mx-auto font-medium">
              Horneando momentos especiales y creando dulces sonrisas con recetas tradicionales y toques modernos.
            </p>
          </div>
        </section>

        {/* Business Description */}
        <section className="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <div className="space-y-4 text-center">
            <span className="inline-block text-[10px] font-bold uppercase tracking-widest bg-secondary/15 text-primary px-3 py-1.5 rounded-lg border border-secondary/30">
              Sobre Dulce Encanto
            </span>
            <Typography variant="h2" className="text-2xl sm:text-3xl font-black text-primary">
              La Magia de la Repostería Artesanal
            </Typography>
          </div>
          
          <div className="text-text-sub text-xs sm:text-sm leading-relaxed space-y-6 font-semibold text-justify">
            <p>
              En <strong>Dulce Encanto</strong>, creemos que la pastelería es mucho más que mezclar ingredientes; es una forma de arte que transmite emociones y celebra la vida. Nacimos con el firme propósito de ofrecer postres que no solo conquisten por su presentación visual, sino que dejen una huella imborrable en el paladar por su sabor excepcional.
            </p>
            <p>
              Nuestra filosofía de trabajo se centra en el respeto por las recetas tradicionales y la constante exploración de técnicas contemporáneas de decoración y pastelería fina. Cada producto que sale de nuestro horno es el resultado de un riguroso proceso de control y amor por la cocina, garantizando una textura y frescura insuperables.
            </p>
            <p>
              A lo largo de nuestra trayectoria, nos hemos especializado en entender y materializar los sueños de nuestros clientes. Ya sea un pastel temático detallado o una sofisticada mesa de bocaditos dulces para bodas, nos comprometemos a brindar un servicio impecable y personalizado para que cada celebración sea inolvidable.
            </p>
          </div>
        </section>

        {/* Highlights Grid */}
        <section className="bg-surface py-16 border-t border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center space-y-3 max-w-xl mx-auto mb-12">
              <Typography variant="h2" className="text-2xl font-black text-primary">¿Qué nos distingue?</Typography>
              <Typography variant="body" className="text-text-sub text-xs font-semibold">
                Nuestros pilares fundamentales para garantizar una experiencia gourmet en cada pedido.
              </Typography>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {highlights.map((h, idx) => {
                const Icon = h.icon
                return (
                  <Card key={idx} className="bg-background border border-border shadow-sm">
                    <CardContent className="p-6 flex items-start gap-4">
                      <div className="w-10 h-10 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-primary text-lg shrink-0">
                        <Icon />
                      </div>
                      <div className="space-y-2">
                        <Typography variant="h3" className="text-sm font-black text-primary">{h.title}</Typography>
                        <p className="text-text-sub text-xs leading-relaxed font-semibold font-sans">{h.desc}</p>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  )
}
