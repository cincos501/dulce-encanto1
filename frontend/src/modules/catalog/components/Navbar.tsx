import React, { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { FiShoppingBag, FiUser, FiStar, FiMenu, FiX, FiSun, FiMoon } from 'react-icons/fi'
import { Typography } from '@/design-system'
import { useTheme } from '@/app/providers/ThemeContext'

interface NavbarProps {
  cartCount?: number;
  onCartClick?: () => void;
}

export default function Navbar({ cartCount = 0, onCartClick }: NavbarProps) {
  const location = useLocation()
  const [isMenuOpen, setIsMenuOpen] = useState<boolean>(false)
  const { theme, toggleTheme } = useTheme()

  const links = [
    { name: 'Inicio', path: '/' },
    { name: 'Nosotros', path: '/nosotros' },
    { name: 'Servicios', path: '/servicios' },
    { name: 'Nuestro Menú', path: '/menu' },
    { name: 'Promociones', path: '/promociones' },
    { name: 'Contacto', path: '/contacto' }
  ]

  const closeMenu = () => setIsMenuOpen(false)

  return (
    <header className="sticky top-0 z-40 backdrop-blur-md bg-surface/80 border-b border-border transition-all duration-300">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        {/* Logo */}
        <Link to="/" className="flex items-center gap-2 select-none group z-50">
          <FiStar className="text-xl text-amber-500 fill-amber-500 animate-pulse shrink-0" />
          <span className="font-heading font-black text-xl text-primary tracking-tight group-hover:text-amber-800 transition-colors">
            Dulce Encanto
          </span>
        </Link>
        
        {/* Navigation (Desktop) */}
        <nav className="hidden md:flex items-center gap-8 text-[10px] font-bold uppercase tracking-widest text-text-sub font-sans">
          {links.map((link) => {
            const isActive = location.pathname === link.path
            return (
              <Link 
                key={link.path} 
                to={link.path} 
                className={`transition-colors duration-200 ${
                  isActive ? 'text-primary font-black border-b border-primary/50 pb-0.5' : 'hover:text-primary'
                }`}
              >
                {link.name}
              </Link>
            )
          })}
        </nav>

        {/* Action icons */}
        <div className="flex items-center gap-4 z-50">
          {/* Theme Toggle (Desktop) */}
          <button
            onClick={toggleTheme}
            className="relative w-12 h-6 bg-stone-200 dark:bg-stone-700 rounded-full flex items-center justify-between px-1 cursor-pointer transition-all duration-300 border border-stone-300 dark:border-stone-600 shadow-inner focus:outline-none shrink-0"
            title={theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
          >
            {/* Sliding knob with icon */}
            <div className={`w-4 h-4 rounded-full bg-white dark:bg-stone-900 shadow-md border border-stone-300 dark:border-stone-700 flex items-center justify-center transition-transform duration-300 ${
              theme === 'dark' ? 'translate-x-6' : 'translate-x-0'
            }`}>
              {theme === 'dark' ? (
                <FiSun className="w-2.5 h-2.5 text-amber-400 fill-amber-400 rotate-12 transition-transform duration-500" />
              ) : (
                <FiMoon className="w-2.5 h-2.5 text-stone-700 -rotate-12 transition-transform duration-500" />
              )}
            </div>
            {/* Inactive background icons */}
            <div className="absolute inset-0 flex items-center justify-between px-1.5 pointer-events-none">
              <FiSun className={`w-2.5 h-2.5 text-amber-500 ${theme === 'dark' ? 'opacity-0' : 'opacity-100'} transition-opacity duration-300`} />
              <FiMoon className={`w-2.5 h-2.5 text-stone-400 ${theme === 'dark' ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`} />
            </div>
          </button>

          <div 
            onClick={onCartClick}
            className="relative p-2 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg text-text-sub hover:text-primary transition-all duration-200 cursor-pointer"
          >
            <FiShoppingBag className="w-5 h-5 stroke-[1.5]" />
            {cartCount > 0 && (
              <span className="absolute top-0.5 right-0.5 bg-primary text-primary-foreground text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center animate-bounce shadow-md">
                {cartCount}
              </span>
            )}
          </div>
          
          <Link 
            to="/login" 
            className="hidden sm:inline-flex items-center gap-1.5 bg-stone-900 hover:bg-stone-800 dark:bg-amber-600 dark:hover:bg-amber-500 text-white px-4 py-2 rounded-lg font-bold transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans shadow-sm"
          >
            <FiUser className="text-sm shrink-0" />
            <span>Ingresar</span>
          </Link>

          {/* Hamburger Menu (Mobile) */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="md:hidden p-2 text-text-sub hover:text-primary hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
            aria-label="Menu Principal"
          >
            {isMenuOpen ? <FiX className="text-xl" /> : <FiMenu className="text-xl" />}
          </button>
        </div>
      </div>

      {/* MOBILE DRAWER OVERLAY */}
      {isMenuOpen && (
        <div className="fixed inset-0 top-20 bg-stone-900/40 backdrop-blur-sm md:hidden z-40 animate-fade-in" onClick={closeMenu}>
          <div 
            className="bg-surface border-b border-border p-6 space-y-6 animate-slide-down shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          >
            <nav className="flex flex-col gap-3 text-xs font-bold uppercase tracking-widest text-text-sub font-sans">
              {links.map((link) => {
                const isActive = location.pathname === link.path
                return (
                  <Link 
                    key={link.path} 
                    to={link.path} 
                    onClick={closeMenu}
                    className={`py-2.5 px-3.5 rounded-xl transition-colors ${
                      isActive ? 'bg-secondary/15 text-primary font-black' : 'hover:bg-stone-100 dark:hover:bg-stone-800 hover:text-primary'
                    }`}
                  >
                    {link.name}
                  </Link>
                )
              })}
            </nav>

            <div className="border-t border-border my-2" />

            {/* Theme Toggle (Mobile Row) */}
            <div className="flex items-center justify-between bg-stone-100 dark:bg-stone-800/80 border border-stone-200 dark:border-stone-700/80 p-4 rounded-xl shadow-sm">
              <div className="space-y-0.5">
                <span className="text-[11px] font-bold text-stone-900 dark:text-stone-100 block uppercase tracking-wider">Apariencia del Sitio</span>
                <span className="text-[10px] text-stone-500 dark:text-stone-400 block leading-none font-semibold">
                  {theme === 'dark' ? 'Modo Oscuro Activo' : 'Modo Claro Activo'}
                </span>
              </div>
              
              <button
                onClick={toggleTheme}
                className="relative w-12 h-6 bg-stone-200 dark:bg-stone-700 rounded-full flex items-center justify-between px-1 cursor-pointer transition-all duration-300 border border-stone-300 dark:border-stone-600 shadow-inner focus:outline-none shrink-0"
                title={theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
              >
                {/* Sliding knob with icon */}
                <div className={`w-4 h-4 rounded-full bg-white dark:bg-stone-900 shadow-md border border-stone-300 dark:border-stone-700 flex items-center justify-center transition-transform duration-300 ${
                  theme === 'dark' ? 'translate-x-6' : 'translate-x-0'
                }`}>
                  {theme === 'dark' ? (
                    <FiSun className="w-2.5 h-2.5 text-amber-400 fill-amber-400 rotate-12 transition-transform duration-500" />
                  ) : (
                    <FiMoon className="w-2.5 h-2.5 text-stone-700 -rotate-12 transition-transform duration-500" />
                  )}
                </div>
                {/* Inactive background icons */}
                <div className="absolute inset-0 flex items-center justify-between px-1.5 pointer-events-none">
                  <FiSun className={`w-2.5 h-2.5 text-amber-500 ${theme === 'dark' ? 'opacity-0' : 'opacity-100'} transition-opacity duration-300`} />
                  <FiMoon className={`w-2.5 h-2.5 text-stone-400 ${theme === 'dark' ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`} />
                </div>
              </button>
            </div>

            <Link 
              to="/login" 
              onClick={closeMenu}
              className="flex items-center justify-center gap-2 bg-stone-900 hover:bg-stone-800 dark:bg-amber-600 dark:hover:bg-amber-500 text-white w-full py-3.5 rounded-xl font-bold transition-all text-xs uppercase tracking-wider font-sans shadow-md"
            >
              <FiUser className="text-sm shrink-0" />
              <span>Acceso Personal</span>
            </Link>
          </div>
        </div>
      )}
    </header>
  )
}
