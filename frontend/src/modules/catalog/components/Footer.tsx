import React from 'react'
import { Typography } from '@/design-system'

export default function Footer() {
  return (
    <footer className="bg-surface text-text-sub py-12 border-t border-border">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
        <Typography variant="h3" className="text-primary font-heading text-lg">
          Pastelería Dulce Encanto
        </Typography>
        <p className="text-[10px] text-text-sub/80 max-w-md mx-auto font-semibold leading-relaxed font-sans">
          Elaboramos con pasión las mejores delicias gourmet para tus fechas especiales. Visítanos en nuestra sucursal central o realiza tus pedidos en línea.
        </p>
        <div className="text-[9px] text-text-sub/60 font-bold uppercase tracking-widest pt-4 border-t border-border/80 font-sans">
          &copy; {new Date().getFullYear()} Dulce Encanto &bull; Todos los derechos reservados.
        </div>
      </div>
    </footer>
  )
}
