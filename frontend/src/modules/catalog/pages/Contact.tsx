import React from 'react'
import { Typography, Card, CardContent, Button } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiPhone, FiMail, FiMapPin, FiClock, FiInstagram, FiFacebook } from 'react-icons/fi'
import { FaWhatsapp, FaTiktok } from 'react-icons/fa'

export default function Contact() {
  const contactsInfo = [
    { title: 'WhatsApp Principal', detail: '+591 700 12345', icon: FaWhatsapp, subtitle: 'Pedidos y Consultas rápidas' },
    { title: 'Teléfono Fijo', detail: '+591 3 344 5566', icon: FiPhone, subtitle: 'Atención en sucursal' },
    { title: 'Correo Electrónico', detail: 'contacto@dulceencanto.bo', icon: FiMail, subtitle: 'Eventos y Cotizaciones corporativas' },
    { title: 'Dirección Sucursal Central', detail: 'Equipetrol Calle 8 Este, Santa Cruz de la Sierra, Bolivia', icon: FiMapPin, subtitle: 'Ven a visitarnos' },
    { title: 'Horarios de Atención', detail: 'Martes a Domingo: 9:00 AM - 8:00 PM', icon: FiClock, subtitle: 'Lunes Cerrado' }
  ]

  const socialLinks = [
    { name: 'Instagram', url: 'https://instagram.com', icon: FiInstagram, color: 'text-pink-600 dark:text-pink-400 hover:bg-pink-50 dark:hover:bg-pink-950/20' },
    { name: 'Facebook', url: 'https://facebook.com', icon: FiFacebook, color: 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20' },
    { name: 'TikTok', url: 'https://tiktok.com', icon: FaTiktok, color: 'text-stone-900 dark:text-stone-100 hover:bg-stone-100 dark:hover:bg-stone-800' }
  ]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12 animate-fade-in">
        {/* Title */}
        <div className="text-center space-y-3">
          <Typography variant="h1" className="text-3xl font-black text-primary">Contacto</Typography>
          <Typography variant="body" className="text-text-sub text-xs font-semibold font-sans">
            ¿Deseas realizar un pedido especial o tienes alguna consulta? Estamos listos para atenderte.
          </Typography>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
          {/* Contact Details & WhatsApp Call To Action */}
          <div className="lg:col-span-5 flex flex-col justify-between space-y-6">
            <div className="space-y-4">
              {contactsInfo.map((info, idx) => {
                const Icon = info.icon
                return (
                  <Card key={idx} className="bg-surface border border-border shadow-sm">
                    <CardContent className="p-4 flex items-start gap-4">
                      <div className="w-9 h-9 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-primary text-base shrink-0 select-none">
                        <Icon />
                      </div>
                      <div className="space-y-1 leading-tight">
                        <span className="text-[9px] text-text-sub uppercase tracking-wider font-bold block">{info.title}</span>
                        <span className="text-xs font-black text-primary block">{info.detail}</span>
                        <span className="text-[8px] text-text-sub font-semibold block">{info.subtitle}</span>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>

            {/* Social Media Links */}
            <Card className="bg-surface border border-border shadow-sm p-5 space-y-4">
              <Typography variant="label" className="text-[10px] uppercase tracking-wider font-bold text-primary block">
                Nuestras Redes Sociales
              </Typography>
              <div className="flex gap-4">
                {socialLinks.map((s, idx) => {
                  const Icon = s.icon
                  return (
                    <a
                      key={idx}
                      href={s.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className={`flex items-center gap-2 px-3 py-2 border border-border rounded-lg text-xs font-bold transition-all duration-200 cursor-pointer ${s.color}`}
                    >
                      <Icon className="text-sm" />
                      <span>{s.name}</span>
                    </a>
                  )
                })}
              </div>
            </Card>
          </div>

          {/* WhatsApp Primary Call to Action & Map */}
          <div className="lg:col-span-7 flex flex-col justify-between space-y-6">
            {/* Primary CTA Card */}
            <Card className="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 shadow-sm p-6 sm:p-8 flex flex-col items-center justify-center text-center space-y-5">
              <div className="w-16 h-16 rounded-full bg-emerald-500 text-white flex items-center justify-center text-3xl shadow-md animate-pulse">
                <FaWhatsapp />
              </div>
              <div className="space-y-2">
                <Typography variant="h2" className="text-xl font-black text-emerald-800 dark:text-emerald-300">
                  ¿Listo para hacer tu pedido?
                </Typography>
                <p className="text-stone-600 dark:text-stone-300 text-xs font-semibold leading-relaxed max-w-sm">
                  Conversa directamente con nosotros para personalizar tus tortas de cumpleaños, postres temáticos, mesas dulces o consultar la disponibilidad del día.
                </p>
              </div>
              <a 
                href="https://wa.me/59170012345" 
                target="_blank" 
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-8 py-3 rounded-lg text-xs uppercase tracking-wider font-sans shadow-md hover:shadow-lg transition-all duration-200 active:scale-95 cursor-pointer"
              >
                <FaWhatsapp className="text-base" />
                <span>Escríbenos por WhatsApp</span>
              </a>
            </Card>

            {/* Embedded Google Maps Location */}
            <Card className="bg-surface border border-border shadow-sm p-2 overflow-hidden h-72 rounded-lg relative flex items-center justify-center text-center">
              <iframe
                title="Ubicacion Pasteleria Dulce Encanto"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3799.309062035319!2d-63.1843126!3d-17.766779!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTfCsDQ2JzAwLjQiUyA2M8KwMTEnMDMuNSJX!5e0!3m2!1ses!2sbo!4v1657800000000!5m2!1ses!2sbo"
                width="100%"
                height="100%"
                style={{ border: 0 }}
                allowFullScreen={true}
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
                className="absolute inset-0"
              />
            </Card>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  )
}
