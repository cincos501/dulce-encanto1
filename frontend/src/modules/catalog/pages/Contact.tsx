import React from 'react'
import { Typography, Card, CardContent, Button, Input, Textarea, Label } from '@/design-system'
import Navbar from '../components/Navbar'
import Footer from '../components/Footer'
import { FiPhone, FiMail, FiMapPin, FiClock } from 'react-icons/fi'
import { toast } from 'sonner'

export default function Contact() {
  const handleSend = (e: React.FormEvent) => {
    e.preventDefault()
    toast.success('Mensaje enviado. ¡Nos contactaremos a la brevedad!')
    const form = e.target as HTMLFormElement
    form.reset()
  }

  const contactsInfo = [
    { title: 'WhatsApp / Teléfono', detail: '+54 9 11 1234-5678', icon: FiPhone, subtitle: 'Atención inmediata' },
    { title: 'Correo Electrónico', detail: 'hola@dulceencanto.com', icon: FiMail, subtitle: 'Presupuestos y cotizaciones' },
    { title: 'Nuestra Sucursal', detail: 'Av. Corrientes 1234, CABA, Argentina', icon: FiMapPin, subtitle: 'Ven a visitarnos' },
    { title: 'Horarios de Atención', detail: 'Martes a Domingo: 9:00 AM - 8:00 PM', icon: FiClock, subtitle: 'Lunes Cerrado' }
  ]

  return (
    <div className="min-h-screen bg-background text-text-main font-sans flex flex-col justify-between">
      <Navbar />

      <main className="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12">
        {/* Title */}
        <div className="text-center space-y-3">
          <Typography variant="h1" className="text-3xl font-black text-primary">Contacto</Typography>
          <Typography variant="body" className="text-text-sub text-xs font-semibold font-sans">
            ¿Tienes alguna consulta, sugerencia o deseas realizar un pedido especial? Escríbenos.
          </Typography>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Contact details */}
          <div className="lg:col-span-5 space-y-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-6">
              {contactsInfo.map((info, idx) => {
                const Icon = info.icon
                return (
                  <Card key={idx} className="bg-surface border border-border shadow-sm">
                    <CardContent className="p-5 flex items-start gap-4">
                      <div className="w-10 h-10 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-primary text-base shrink-0 select-none">
                        <Icon />
                      </div>
                      <div className="space-y-1 leading-tight">
                        <span className="text-[10px] text-text-sub uppercase tracking-wider font-bold block">{info.title}</span>
                        <span className="text-xs font-black text-primary block">{info.detail}</span>
                        <span className="text-[9px] text-text-sub font-semibold block">{info.subtitle}</span>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          </div>

          {/* Contact form & Map placeholder */}
          <div className="lg:col-span-7 space-y-6">
            <Card className="bg-surface border border-border shadow-sm p-6 sm:p-8">
              <Typography variant="h3" className="text-base uppercase tracking-wider font-bold mb-5">Envíanos un mensaje</Typography>
              <form onSubmit={handleSend} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="name" required>Nombre Completo</Label>
                    <Input id="name" placeholder="Ej. Ana Pérez" required />
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="email" required>Correo Electrónico</Label>
                    <Input id="email" type="email" placeholder="ana@ejemplo.com" required />
                  </div>
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="subject" required>Asunto</Label>
                  <Input id="subject" placeholder="Ej. Presupuesto para cumpleaños" required />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="message" required>Mensaje</Label>
                  <Textarea id="message" rows={4} placeholder="Detalla lo que necesitas y te responderemos pronto..." required />
                </div>
                <Button type="submit" variant="primary" className="w-full py-3 text-xs uppercase tracking-wider font-bold">
                  Enviar Mensaje
                </Button>
              </form>
            </Card>

            {/* Map placeholder */}
            <Card className="bg-surface border border-border shadow-sm p-2 overflow-hidden h-72 rounded-lg relative flex items-center justify-center text-center">
              <div className="absolute inset-0 bg-background flex flex-col items-center justify-center p-8 select-none">
                <FiMapPin className="text-4xl text-text-sub/40 mb-2 stroke-[1.5]" />
                <h4 className="font-heading font-black text-sm text-primary">Mapa de Ubicación</h4>
                <p className="text-text-sub text-[10px] max-w-xs mt-1 font-semibold leading-relaxed">
                  Av. Corrientes 1234, CABA, Argentina. Sucursal central a pasos del Obelisco.
                </p>
              </div>
            </Card>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  )
}
