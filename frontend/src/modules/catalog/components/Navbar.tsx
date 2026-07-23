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
            className="relative w-12 h-6 bg-stone-200 dark:bg-stone-800 rounded-full flex items-center justify-between px-1 cursor-pointer transition-all duration-300 border border-border shadow-inner focus:outline-none focus:ring-2 focus:ring-primary/20 shrink-0"
            title={theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
          >
            {/* Sliding knob with icon */}
            <div className={`w-4 h-4 rounded-full bg-white dark:bg-stone-900 shadow-md border border-stone-200 dark:border-stone-700 flex items-center justify-center transition-transform duration-300 ${
              theme === 'dark' ? 'translate-x-6' : 'translate-x-0'
            }`}>
              {theme === 'dark' ? (
                <FiSun className="w-2.5 h-2.5 text-amber-500 fill-amber-500 rotate-12 transition-transform duration-500" />
              ) : (
                <FiMoon className="w-2.5 h-2.5 text-stone-755 dark:text-stone-300 -rotate-12 transition-transform duration-500" />
              )}
            </div>
            {/* Inactive background icons */}
            <div className="absolute inset-0 flex items-center justify-between px-1.5 pointer-events-none text-text-sub/30">
              <FiSun className={`w-2.5 h-2.5 ${theme === 'dark' ? 'opacity-0' : 'opacity-100'} transition-opacity duration-300`} />
              <FiMoon className={`w-2.5 h-2.5 ${theme === 'dark' ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`} />
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
            className="hidden sm:inline-flex items-center gap-1.5 bg-primary text-primary-foreground px-4 py-2 rounded-lg font-bold hover:bg-stone-850 dark:hover:bg-stone-155 transition-all duration-200 active:scale-95 text-xs uppercase tracking-wider font-sans"
          >
            <FiUser className="text-sm shrink-0" />
            <span>Ingresar</span>
          </Link>

          {/* Hamburger Menu (Mobile) */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="md:hidden p-2 text-text-sub hover:text-primary hover:bg-stone-100 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
            aria-label="Menu Principal"
          >
            {isMenuOpen ? <FiX className="text-xl" /> : <FiMenu className="text-xl" />}
          </button>
        </div>
      </div>

      {/* MOBILE DRAWER OVERLAY */}
      {isMenuOpen && (
        <div className="fixed inset-0 top-20 bg-stone-900/20 backdrop-blur-sm md:hidden z-40 animate-fade-in" onClick={closeMenu}>
          <div 
            className="bg-surface border-b border-border p-6 space-y-6 animate-slide-down shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <nav className="flex flex-col gap-4 text-xs font-bold uppercase tracking-widest text-text-sub font-sans">
              {links.map((link) => {
                const isActive = location.pathname === link.path
                return (
                  <Link 
                    key={link.path} 
                    to={link.path} 
                    onClick={closeMenu}
                    className={`py-2 px-3 rounded-lg transition-colors ${
                      isActive ? 'bg-secondary/15 text-primary font-black' : 'hover:bg-stone-100 dark:hover:bg-stone-800 hover:text-primary'
                    }`}
                  >
                    {link.name}
                  </Link>
                )
              })}
            </nav>

            <div className="border-t border-border my-4" />

            {/* Theme Toggle (Mobile Row) */}
            <div className="flex items-center justify-between bg-stone-50 dark:bg-stone-850 border border-border p-3.5 rounded-lg">
              <div className="space-y-0.5">
                <span className="text-[10px] font-bold text-primary block uppercase tracking-wider">Apariencia del Sitio</span>
                <span className="text-[9px] text-text-sub block leading-none font-semibold">
                  {theme === 'dark' ? 'Modo Oscuro Activo' : 'Modo Claro Activo'}
                </span>
              </div>
              
              <button
                onClick={toggleTheme}
                className="relative w-12 h-6 bg-stone-200 dark:bg-stone-800 rounded-full flex items-center justify-between px-1 cursor-pointer transition-all duration-300 border border-border shadow-inner focus:outline-none focus:ring-2 focus:ring-primary/20 shrink-0"
                title={theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
              >
                {/* Sliding knob with icon */}
                <div className={`w-4 h-4 rounded-full bg-white dark:bg-stone-900 shadow-md border border-stone-200 dark:border-stone-700 flex items-center justify-center transition-transform duration-300 ${
                  theme === 'dark' ? 'translate-x-6' : 'translate-x-0'
                }`}>
                  {theme === 'dark' ? (
                    <FiSun className="w-2.5 h-2.5 text-amber-500 fill-amber-500 rotate-12 transition-transform duration-500" />
                  ) : (
                    <FiMoon className="w-2.5 h-2.5 text-stone-755 dark:text-stone-300 -rotate-12 transition-transform duration-500" />
                  )}
                </div>
                {/* Inactive background icons */}
                <div className="absolute inset-0 flex items-center justify-between px-1.5 pointer-events-none text-text-sub/30">
                  <FiSun className={`w-2.5 h-2.5 ${theme === 'dark' ? 'opacity-0' : 'opacity-100'} transition-opacity duration-300`} />
                  <FiMoon className={`w-2.5 h-2.5 ${theme === 'dark' ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`} />
                </div>
              </button>
            </div>

            <Link 
              to="/login" 
              onClick={closeMenu}
              className="flex items-center justify-center gap-2 bg-primary text-primary-foreground w-full py-3 rounded-lg font-bold hover:bg-stone-850 dark:hover:bg-stone-155 transition-colors text-xs uppercase tracking-wider font-sans"
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
