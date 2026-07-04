import { useState } from 'react'
import './App.css'

function App() {
  const [activeCategory, setActiveCategory] = useState('todos')
  const [cartCount, setCartCount] = useState(0)

  const categories = [
    { id: 'todos', name: 'Todos' },
    { id: 'pasteles', name: 'Pasteles' },
    { id: 'galletas', name: 'Galletas' },
    { id: 'postres', name: 'Postres Especiales' }
  ]

  const products = [
    {
      id: 1,
      name: 'Pastel de Fresa Silvestre',
      category: 'pasteles',
      price: '$24.99',
      description: 'Bizcocho esponjoso con crema de vainilla de Madagascar y fresas frescas cultivadas orgánicamente.',
      tag: 'Más Vendido',
      gradient: 'from-pink-100 to-rose-200',
      icon: '🍰'
    },
    {
      id: 2,
      name: 'Macarons de Lavanda & Limón',
      category: 'postres',
      price: '$12.50',
      description: 'Delicados macarons franceses rellenos de ganache de chocolate blanco con infusión de lavanda y limón.',
      tag: 'Nuevo',
      gradient: 'from-purple-100 to-indigo-100',
      icon: '🧁'
    },
    {
      id: 3,
      name: 'Galletas de Chispas & Sal Marina',
      category: 'galletas',
      price: '$8.00',
      description: 'Crujientes por fuera, suaves por dentro. Con chocolate belga semi-amargo y un toque de sal marina de Córcega.',
      tag: 'Clásico',
      gradient: 'from-amber-100 to-orange-100',
      icon: '🍪'
    },
    {
      id: 4,
      name: 'Tarta de Frutos Rojos',
      category: 'pasteles',
      price: '$22.00',
      description: 'Base de hojaldre crujiente rellena de crema pastelera sedosa y una corona abundante de frutos del bosque.',
      tag: 'Estacional',
      gradient: 'from-red-100 to-pink-200',
      icon: '🥧'
    },
    {
      id: 5,
      name: 'Alfajores de Dulce de Leche',
      category: 'galletas',
      price: '$9.50',
      description: 'Galletas de maicena ultra suaves rellenas con abundante dulce de leche repostero y espolvoreadas con coco.',
      tag: 'Popular',
      gradient: 'from-yellow-100 to-amber-200',
      icon: '🥯'
    },
    {
      id: 6,
      name: 'Mousse de Maracuyá & Mango',
      category: 'postres',
      price: '$14.00',
      description: 'Textura ligera y aireada con el balance perfecto de acidez del maracuyá y el dulzor del mango fresco.',
      tag: 'Refrescante',
      gradient: 'from-yellow-100 to-orange-200',
      icon: '🍹'
    }
  ]

  const filteredProducts = activeCategory === 'todos' 
    ? products 
    : products.filter(p => p.category === activeCategory)

  return (
    <div className="min-h-screen bg-gradient-to-b from-rose-50/50 via-amber-50/30 to-white text-stone-800 font-sans selection:bg-rose-200 selection:text-rose-900">
      
      {/* HEADER */}
      <header className="sticky top-0 z-50 backdrop-blur-md bg-white/75 border-b border-rose-100/50 transition-all duration-300">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-3xl">✨</span>
            <span className="font-extrabold text-2xl bg-gradient-to-r from-rose-500 via-pink-600 to-amber-500 bg-clip-text text-transparent tracking-tight">
              Dulce Encanto
            </span>
          </div>
          
          <nav className="hidden md:flex items-center gap-8 font-medium text-stone-600">
            <a href="#inicio" className="hover:text-rose-500 transition-colors duration-200">Inicio</a>
            <a href="#menu" className="hover:text-rose-500 transition-colors duration-200">Nuestro Menú</a>
            <a href="#nosotros" className="hover:text-rose-500 transition-colors duration-200">Nosotros</a>
            <a href="#contacto" className="hover:text-rose-500 transition-colors duration-200">Contacto</a>
          </nav>

          <div className="flex items-center gap-4">
            <button 
              className="relative p-2.5 hover:bg-rose-50 rounded-full text-stone-700 hover:text-rose-500 transition-all duration-200 active:scale-95"
              aria-label="Carrito"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="w-6 h-6">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
              </svg>
              {cartCount > 0 && (
                <span className="absolute top-0 right-0 bg-gradient-to-r from-rose-500 to-pink-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center animate-bounce shadow-md">
                  {cartCount}
                </span>
              )}
            </button>
            
            <a 
              href="#menu" 
              className="bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white px-5 py-2.5 rounded-full font-semibold shadow-lg shadow-rose-200/50 hover:shadow-rose-300/80 transition-all duration-200 active:scale-95 text-sm"
            >
              Ordenar Ahora
            </a>
          </div>
        </div>
      </header>

      {/* HERO SECTION */}
      <section id="inicio" className="relative overflow-hidden py-16 lg:py-24">
        {/* Decorative background blobs */}
        <div className="absolute top-0 left-1/4 w-96 h-96 bg-rose-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
        <div className="absolute bottom-10 right-1/4 w-96 h-96 bg-amber-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse delay-700"></div>
        
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div className="lg:col-span-7 space-y-6 text-center lg:text-left">
              <span className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-100">
                🧁 Repostería Artesanal Premium
              </span>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-stone-900 leading-tight tracking-tight">
                Momentos dulces para <br className="hidden sm:inline" />
                <span className="bg-gradient-to-r from-rose-500 via-pink-600 to-amber-500 bg-clip-text text-transparent">
                  enamorar el paladar
                </span>
              </h1>
              <p className="text-lg text-stone-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                En <strong>Dulce Encanto</strong>, horneamos felicidad a diario. Descubre nuestra selección exclusiva de pasteles, galletas y postres gourmet creados con ingredientes 100% naturales y una pasión inigualable.
              </p>
              <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                <a 
                  href="#menu" 
                  className="w-full sm:w-auto text-center bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white px-8 py-4 rounded-full font-bold shadow-xl shadow-rose-200/50 hover:shadow-rose-300/80 transition-all duration-200 active:scale-95"
                >
                  Explorar Menú
                </a>
                <a 
                  href="#nosotros" 
                  className="w-full sm:w-auto text-center bg-white hover:bg-stone-50 text-stone-700 border border-stone-200 px-8 py-4 rounded-full font-bold transition-all duration-200 active:scale-95"
                >
                  Nuestra Historia
                </a>
              </div>
            </div>

            <div className="lg:col-span-5 flex justify-center">
              <div className="relative">
                {/* Visual wrapper representing a plate / beautiful display */}
                <div className="w-80 h-80 sm:w-96 sm:h-96 rounded-full bg-gradient-to-tr from-rose-100 to-amber-100 absolute -inset-3 blur-md opacity-70 animate-spin-slow"></div>
                <div className="w-80 h-80 sm:w-96 sm:h-96 rounded-full bg-white shadow-2xl relative flex flex-col items-center justify-center border border-rose-100 p-8 text-center overflow-hidden">
                  <div className="text-8xl mb-4 transform hover:rotate-12 transition-transform duration-300 cursor-pointer">🎂</div>
                  <h3 className="font-extrabold text-2xl text-stone-900">Especial del Día</h3>
                  <p className="text-rose-500 font-semibold mt-1">Red Velvet Supreme</p>
                  <p className="text-stone-500 text-xs mt-2 px-6">Crema suave de queso mascarpone y capas esponjosas de cacao selecto.</p>
                  <button 
                    onClick={() => setCartCount(prev => prev + 1)}
                    className="mt-4 bg-stone-900 hover:bg-stone-800 text-white text-xs font-bold px-4 py-2.5 rounded-full transition-all duration-200 active:scale-95"
                  >
                    Agregar al pedido
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* VALUE PROPOSITION */}
      <section className="bg-white py-16 border-y border-rose-100/50">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div className="p-6 space-y-3">
              <div className="text-4xl">🍓</div>
              <h4 className="font-bold text-lg text-stone-900">Ingredientes Premium</h4>
              <p className="text-stone-600 text-sm">Frutas frescas de temporada, chocolates belgas selectos y mantequilla 100% de origen normando.</p>
            </div>
            <div className="p-6 space-y-3">
              <div className="text-4xl">❤️</div>
              <h4 className="font-bold text-lg text-stone-900">Hecho con Amor</h4>
              <p className="text-stone-600 text-sm">Cada postre es elaborado a mano por maestros pasteleros con atención absoluta al detalle.</p>
            </div>
            <div className="p-6 space-y-3">
              <div className="text-4xl">🚗</div>
              <h4 className="font-bold text-lg text-stone-900">Entrega Perfecta</h4>
              <p className="text-stone-600 text-sm">Despacho refrigerado y empaques reforzados para garantizar que tu postre llegue impecable.</p>
            </div>
          </div>
        </div>
      </section>

      {/* PRODUCTS / MENU */}
      <section id="menu" className="py-20 bg-stone-50/50">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          
          <div className="text-center space-y-4 max-w-xl mx-auto mb-12">
            <h2 className="text-3xl sm:text-4xl font-extrabold text-stone-900">Nuestro Dulce Catálogo</h2>
            <p className="text-stone-600">Filtra entre nuestras delicias horneadas y encuentra la tentación perfecta para tu día.</p>
            
            {/* CATEGORIES BUTTONS */}
            <div className="flex flex-wrap items-center justify-center gap-2 pt-4">
              {categories.map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => setActiveCategory(cat.id)}
                  className={`px-5 py-2 rounded-full font-semibold text-sm transition-all duration-200 active:scale-95 ${
                    activeCategory === cat.id
                      ? 'bg-rose-500 text-white shadow-md shadow-rose-200'
                      : 'bg-white text-stone-600 hover:bg-rose-50 hover:text-rose-500 border border-stone-200/60'
                  }`}
                >
                  {cat.name}
                </button>
              ))}
            </div>
          </div>

          {/* PRODUCTS GRID */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredProducts.map((product) => (
              <div 
                key={product.id}
                className="bg-white rounded-3xl border border-rose-100/50 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full"
              >
                {/* Product Image Placeholder with dynamic background gradient */}
                <div className={`h-48 bg-gradient-to-tr ${product.gradient} relative flex items-center justify-center overflow-hidden transition-all duration-500 group-hover:scale-[1.02]`}>
                  <div className="text-6xl transform group-hover:scale-120 group-hover:rotate-6 transition-all duration-300">{product.icon}</div>
                  <span className="absolute top-4 right-4 bg-white/95 backdrop-blur-sm text-stone-900 text-xs font-bold px-3 py-1 rounded-full border border-rose-100 shadow-sm">
                    {product.tag}
                  </span>
                </div>
                
                {/* Content */}
                <div className="p-6 flex-grow flex flex-col justify-between space-y-4">
                  <div className="space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-xs uppercase font-extrabold tracking-wider text-rose-400">{product.category}</span>
                      <span className="font-extrabold text-lg text-rose-500">{product.price}</span>
                    </div>
                    <h3 className="font-bold text-xl text-stone-900 group-hover:text-rose-500 transition-colors duration-200">
                      {product.name}
                    </h3>
                    <p className="text-stone-600 text-sm leading-relaxed">
                      {product.description}
                    </p>
                  </div>
                  
                  <button 
                    onClick={() => setCartCount(prev => prev + 1)}
                    className="w-full bg-rose-50 hover:bg-rose-500 text-rose-600 hover:text-white py-3 rounded-2xl font-bold text-sm transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 group-hover:bg-rose-500 group-hover:text-white"
                  >
                    <span>Agregar al pedido</span>
                    <span>➕</span>
                  </button>
                </div>
              </div>
            ))}
          </div>

        </div>
      </section>

      {/* FOOTER & CONTACT */}
      <footer id="contacto" className="bg-stone-900 text-stone-400 py-16 border-t border-stone-800">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-12">
            
            <div className="space-y-4">
              <span className="font-extrabold text-2xl text-white tracking-tight flex items-center gap-2">
                <span>✨</span> Dulce Encanto
              </span>
              <p className="text-sm leading-relaxed">
                Elaboramos momentos felices con la mejor repostería fina tradicional y de vanguardia.
              </p>
            </div>

            <div className="space-y-4">
              <h4 className="font-bold text-white text-lg">Horarios</h4>
              <ul className="space-y-2 text-sm">
                <li>Lunes a Viernes: 8:00 AM - 8:00 PM</li>
                <li>Sábados y Domingos: 9:00 AM - 9:00 PM</li>
              </ul>
            </div>

            <div className="space-y-4">
              <h4 className="font-bold text-white text-lg">Contacto</h4>
              <ul className="space-y-2 text-sm">
                <li>📍 Av. Principal Dulce Nro 123</li>
                <li>📞 +56 9 1234 5678</li>
                <li>✉️ contacto@dulceencanto.cl</li>
              </ul>
            </div>

            <div className="space-y-4">
              <h4 className="font-bold text-white text-lg">Redes Sociales</h4>
              <div className="flex gap-4">
                <a href="#instagram" className="hover:text-rose-400 transition-colors">Instagram</a>
                <a href="#facebook" className="hover:text-rose-400 transition-colors">Facebook</a>
                <a href="#tiktok" className="hover:text-rose-400 transition-colors">TikTok</a>
              </div>
            </div>

          </div>

          <div className="border-t border-stone-850 mt-12 pt-8 text-center text-xs">
            <p>&copy; {new Date().getFullYear()} Dulce Encanto S.A. Todos los derechos reservados. Creado con amor y pasión.</p>
          </div>
        </div>
      </footer>
      
    </div>
  )
}

export default App
