import React, { useState } from 'react'
import { Link, useNavigate, useLocation, Outlet } from 'react-router-dom'
import { useAuth } from '@/app/providers/AuthContext'
import { useAuthorization } from '@/shared/hooks/useAuthorization'
import { toast } from 'sonner'
import {
  FiGrid,
  FiFolder,
  FiPackage,
  FiSliders,
  FiTag,
  FiUsers,
  FiLogOut,
  FiMenu,
  FiBell,
  FiSettings,
  FiShoppingBag,
  FiUser,
  FiX,
  FiSun,
  FiMoon,
  FiTruck,
  FiBookOpen,
  FiLayers,
  FiCheckSquare,
  FiBarChart2
} from 'react-icons/fi'
import { useTheme } from '@/app/providers/ThemeContext'

interface MenuItem {
  name: string;
  path: string;
  icon: React.ComponentType<any>;
  permission: string | null;
  section: 'catalog' | 'sales' | 'admin' | 'none';
}

export default function AdminLayout() {
  const { user, logout } = useAuth()
  const { hasPermission } = useAuthorization()
  const { theme, toggleTheme } = useTheme()
  const navigate = useNavigate()
  const location = useLocation()
  const [isLoggingOut, setIsLoggingOut] = useState<boolean>(false)

  // States for responsive menu
  const [isSidebarOpen, setIsSidebarOpen] = useState<boolean>(true) // Desktop collapse state
  const [isMobileOpen, setIsMobileOpen] = useState<boolean>(false) // Mobile open drawer state

  const handleLogout = async () => {
    setIsLoggingOut(true)
    try {
      await logout()
      toast.success('Sesión cerrada con éxito.')
      navigate('/login')
    } catch {
      toast.error('Error al cerrar sesión.')
    } finally {
      setIsLoggingOut(false)
    }
  }

  const menuItems: MenuItem[] = [
    {
      name: 'Dashboard',
      path: '/dashboard',
      icon: FiGrid,
      permission: null,
      section: 'none'
    },
    {
      name: 'Categorías',
      path: '/categories',
      icon: FiFolder,
      permission: 'categories.view',
      section: 'catalog'
    },
    {
      name: 'Productos',
      path: '/products',
      icon: FiPackage,
      permission: 'products.view',
      section: 'catalog'
    },
    {
      name: 'Extras',
      path: '/extras',
      icon: FiSliders,
      permission: 'extras.view',
      section: 'catalog'
    },
    {
      name: 'Promociones',
      path: '/promotions',
      icon: FiTag,
      permission: 'promotions.view',
      section: 'catalog'
    },
    {
      name: 'Proveedores',
      path: '/suppliers',
      icon: FiTruck,
      permission: 'suppliers.view',
      section: 'inventory'
    },
    {
      name: 'Insumos',
      path: '/supplies',
      icon: FiLayers,
      permission: 'supplies.view',
      section: 'inventory'
    },
    {
      name: 'Recetas',
      path: '/recipes',
      icon: FiBookOpen,
      permission: 'recipes.view',
      section: 'inventory'
    },
    {
      name: 'Pedidos',
      path: '/orders',
      icon: FiShoppingBag,
      permission: 'orders.view',
      section: 'sales'
    },
    {
      name: 'Producción',
      path: '/production',
      icon: FiCheckSquare,
      permission: 'orders.view',
      section: 'sales'
    },
    {
      name: 'Reportes',
      path: '/reports',
      icon: FiBarChart2,
      permission: 'reports.view',
      section: 'admin'
    },
    {
      name: 'Usuarios',
      path: '/users',
      icon: FiUsers,
      permission: 'users.manage',
      section: 'admin'
    }
  ]

  const filteredMenuItems = menuItems.filter(item =>
    item.permission === null || hasPermission(item.permission)
  )

  const renderSection = (sectionName: string, items: MenuItem[], closeOnClick: () => void) => {
    if (items.length === 0) return null
    return (
      <div className="space-y-1">
        <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-3 select-none">
          {sectionName}
        </h4>
        <div className="space-y-0.5">
          {items.map((item) => {
            const isActive = location.pathname === item.path
            const Icon = item.icon
            return (
              <Link
                key={item.name}
                to={item.path}
                onClick={closeOnClick}
                className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 ${isActive
                  ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                  : 'text-text-sub hover:text-primary hover:bg-stone-100/85'
                  }`}
                title={item.name}
              >
                <Icon className="text-sm shrink-0" />
                <span className="lg:block block">{item.name}</span>
              </Link>
            )
          })}
        </div>
      </div>
    )
  }

  const renderSidebarContents = (closeOnClick: () => void) => (
    <div className="flex flex-col h-full justify-between">
      <div className="flex flex-col flex-grow overflow-y-auto">
        {/* Logo Section */}
        <div className="h-20 flex items-center justify-between px-6 border-b border-border/60">
          <div className="flex items-center gap-2.5 overflow-hidden">
            <span className="text-lg leading-none shrink-0 text-amber-500"></span>
            <span className="font-heading font-black text-lg text-primary tracking-tight whitespace-nowrap">
              Dulce Encanto
            </span>
          </div>
          <button
            onClick={closeOnClick}
            className="lg:hidden text-text-sub hover:text-primary p-1.5 rounded-lg hover:bg-stone-50 transition-colors flex items-center justify-center shrink-0"
            title="Cerrar"
          >
            <FiX className="text-sm" />
          </button>
        </div>

        {/* Navigation Menu */}
        <nav className="p-4 space-y-4 font-sans">
          {/* General section */}
          <div className="space-y-0.5">
            {filteredMenuItems.filter(i => i.section === 'none').map((item) => {
              const isActive = location.pathname === item.path
              const Icon = item.icon
              return (
                <Link
                  key={item.name}
                  to={item.path}
                  onClick={closeOnClick}
                  className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 ${isActive
                    ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                    : 'text-text-sub hover:text-primary hover:bg-stone-100/85'
                    }`}
                  title={item.name}
                >
                  <Icon className="text-sm shrink-0" />
                  <span>{item.name}</span>
                </Link>
              )
            })}
          </div>

          {/* Catalog Section */}
          {renderSection('Catálogo', filteredMenuItems.filter(i => i.section === 'catalog'), closeOnClick)}

          {/* Inventory Section */}
          {renderSection('Inventario', filteredMenuItems.filter(i => i.section === 'inventory'), closeOnClick)}

          {/* Sales Section */}
          {renderSection('Ventas', filteredMenuItems.filter(i => i.section === 'sales'), closeOnClick)}

          {/* Sales section placeholder */}
          {filteredMenuItems.filter(i => i.section === 'sales').length === 0 ? (
            <div className="space-y-1">
              <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-3 select-none">
                Ventas
              </h4>
              <div className="opacity-45 cursor-not-allowed space-y-0.5 select-none">
                <div className="flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold text-text-sub">
                  <FiShoppingBag className="text-sm shrink-0" />
                  <span>Pedidos (Próximamente)</span>
                </div>
                <div className="flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold text-text-sub">
                  <FiUser className="text-sm shrink-0" />
                  <span>Clientes (Próximamente)</span>
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-1">
              <div className="opacity-45 cursor-not-allowed space-y-0.5 select-none">
                <div className="flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold text-text-sub">
                  <FiUser className="text-sm shrink-0" />
                  <span>Clientes (Próximamente)</span>
                </div>
              </div>
            </div>
          )}

          {/* Administration Section */}
          {renderSection('Administración', filteredMenuItems.filter(i => i.section === 'admin'), closeOnClick)}
        </nav>
      </div>

      {/* Footer Sidebar / Logout */}
      <div className="p-4 border-t border-border/60 space-y-3 font-sans">
        <div className="p-3 bg-background border border-border/80 rounded-lg">
          <p className="text-xs font-bold text-text-main truncate">{user?.full_name}</p>
          <p className="text-[10px] text-text-sub truncate font-semibold">{user?.email}</p>
        </div>

        <button
          onClick={handleLogout}
          disabled={isLoggingOut}
          className="w-full hover:bg-stone-100 text-text-sub hover:text-primary px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 active:scale-95 flex items-center gap-3.5"
          title="Cerrar Sesión"
        >
          <FiLogOut className="text-sm shrink-0" />
          <span>{isLoggingOut ? 'Saliendo...' : 'Cerrar Sesión'}</span>
        </button>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-background text-text-main flex font-sans overflow-x-hidden relative">

      {/* 1. SIDEBAR FOR DESKTOP */}
      <aside
        className={`bg-surface border-r border-border transition-all duration-300 hidden lg:flex flex-col justify-between fixed h-full z-20 ${isSidebarOpen ? 'w-64' : 'w-20'
          }`}
      >
        <div className="flex flex-col h-full justify-between">
          <div className="flex flex-col flex-grow overflow-y-auto">
            {/* Logo */}
            <div className="h-20 flex items-center justify-between px-6 border-b border-border/60">
              <div className="flex items-center gap-2.5 overflow-hidden">
                {isSidebarOpen && (
                  <span className="font-heading font-black text-lg text-primary tracking-tight whitespace-nowrap">
                    Dulce Encanto
                  </span>
                )}
              </div>
              {isSidebarOpen && (
                <button
                  onClick={() => setIsSidebarOpen(false)}
                  className="text-text-sub hover:text-primary p-1.5 rounded-lg hover:bg-stone-50 transition-colors flex items-center justify-center shrink-0"
                  title="Colapsar"
                >
                  <FiMenu className="text-sm" />
                </button>
              )}
            </div>

            {/* Menu items */}
            <nav className="p-4 space-y-4">
              <div className="space-y-0.5">
                {filteredMenuItems.filter(i => i.section === 'none').map((item) => {
                  const isActive = location.pathname === item.path
                  const Icon = item.icon
                  return (
                    <Link
                      key={item.name}
                      to={item.path}
                      className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-205 ${isActive
                        ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                        : 'text-text-sub hover:text-primary hover:bg-stone-100/80'
                        }`}
                      title={item.name}
                    >
                      <Icon className="text-sm shrink-0" />
                      {isSidebarOpen && <span>{item.name}</span>}
                    </Link>
                  )
                })}
              </div>

              {/* Sections (Only render headers if sidebar is open) */}
              <div>
                {isSidebarOpen && (
                  <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-2 select-none">
                    Catálogo
                  </h4>
                )}
                <div className="space-y-0.5">
                  {filteredMenuItems.filter(i => i.section === 'catalog').map(item => {
                    const isActive = location.pathname === item.path
                    const Icon = item.icon
                    return (
                      <Link
                        key={item.name}
                        to={item.path}
                        className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-205 ${isActive
                          ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                          : 'text-text-sub hover:text-primary hover:bg-stone-100/80'
                          }`}
                        title={item.name}
                      >
                        <Icon className="text-sm shrink-0" />
                        {isSidebarOpen && <span>{item.name}</span>}
                      </Link>
                    )
                  })}
                </div>
              </div>

              <div>
                {isSidebarOpen && filteredMenuItems.some(i => i.section === 'sales') && (
                  <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-2 select-none">
                    Ventas
                  </h4>
                )}
                <div className="space-y-0.5">
                  {filteredMenuItems.filter(i => i.section === 'sales').map(item => {
                    const isActive = location.pathname === item.path
                    const Icon = item.icon
                    return (
                      <Link
                        key={item.name}
                        to={item.path}
                        className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-205 ${isActive
                          ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                          : 'text-text-sub hover:text-primary hover:bg-stone-100/80'
                          }`}
                        title={item.name}
                      >
                        <Icon className="text-sm shrink-0" />
                        {isSidebarOpen && <span>{item.name}</span>}
                      </Link>
                    )
                  })}
                </div>
              </div>

              <div>
                {isSidebarOpen && (
                  <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-2 select-none">
                    Inventario
                  </h4>
                )}
                <div className="space-y-0.5">
                  {filteredMenuItems.filter(i => i.section === 'inventory').map(item => {
                    const isActive = location.pathname === item.path
                    const Icon = item.icon
                    return (
                      <Link
                        key={item.name}
                        to={item.path}
                        className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-205 ${isActive
                          ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                          : 'text-text-sub hover:text-primary hover:bg-stone-100/80'
                          }`}
                        title={item.name}
                      >
                        <Icon className="text-sm shrink-0" />
                        {isSidebarOpen && <span>{item.name}</span>}
                      </Link>
                    )
                  })}
                </div>
              </div>

              <div>
                {isSidebarOpen && (
                  <h4 className="px-4 text-[9px] font-bold text-text-sub uppercase tracking-widest pt-2 select-none">
                    Administración
                  </h4>
                )}
                <div className="space-y-0.5">
                  {filteredMenuItems.filter(i => i.section === 'admin').map(item => {
                    const isActive = location.pathname === item.path
                    const Icon = item.icon
                    return (
                      <Link
                        key={item.name}
                        to={item.path}
                        className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-205 ${isActive
                          ? 'bg-secondary/15 text-primary border border-secondary/35 font-bold'
                          : 'text-text-sub hover:text-primary hover:bg-stone-100/80'
                          }`}
                        title={item.name}
                      >
                        <Icon className="text-sm shrink-0" />
                        {isSidebarOpen && <span>{item.name}</span>}
                      </Link>
                    )
                  })}
                </div>
              </div>
            </nav>
          </div>

          <div className="p-4 border-t border-border/60 space-y-3">
            {isSidebarOpen && (
              <div className="p-3 bg-background border border-border/80 rounded-lg">
                <p className="text-xs font-bold text-text-main truncate">{user?.full_name}</p>
                <p className="text-[10px] text-text-sub truncate font-semibold">{user?.email}</p>
              </div>
            )}
            <button
              onClick={handleLogout}
              disabled={isLoggingOut}
              className={`w-full hover:bg-stone-100 text-text-sub hover:text-primary px-4 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200 active:scale-95 flex items-center gap-3.5 ${!isSidebarOpen && 'justify-center'
                }`}
              title="Cerrar Sesión"
            >
              <FiLogOut className="text-sm shrink-0" />
              {isSidebarOpen && <span>{isLoggingOut ? 'Saliendo...' : 'Cerrar Sesión'}</span>}
            </button>
          </div>
        </div>
      </aside>

      {/* 2. SIDEBAR DRAWER OVERLAY FOR MOBILE & TABLET */}
      {isMobileOpen && (
        <div
          className="fixed inset-0 bg-stone-900/30 backdrop-blur-sm lg:hidden z-30 animate-fade-in"
          onClick={() => setIsMobileOpen(false)}
        >
          <div
            className="w-64 bg-surface border-r border-border h-full shadow-2xl animate-slide-right"
            onClick={(e) => e.stopPropagation()}
          >
            {renderSidebarContents(() => setIsMobileOpen(false))}
          </div>
        </div>
      )}

      {/* Expand sidebar button when collapsed in desktop */}
      {!isSidebarOpen && (
        <button
          onClick={() => setIsSidebarOpen(true)}
          className="fixed bottom-6 left-6 bg-surface border border-border shadow-sm p-3 rounded-full hover:bg-stone-50 text-text-sub hover:text-primary transition-all duration-200 z-30 hidden lg:flex items-center justify-center cursor-pointer"
          title="Expandir menú"
        >
          <FiMenu className="text-sm" />
        </button>
      )}

      {/* 3. MAIN CONTENT CONTAINER */}
      <div
        className={`flex-grow min-h-screen flex flex-col transition-all duration-300 w-full ${isSidebarOpen ? 'lg:pl-64' : 'lg:pl-20'
          } pl-0`}
      >
        {/* TOP BAR / HEADER */}
        <header className="h-20 bg-surface/75 backdrop-blur-md border-b border-border sticky top-0 z-10 flex items-center justify-between px-4 sm:px-8">
          <div className="flex items-center gap-3">
            {/* Hamburger button for Mobile & Tablet */}
            <button
              onClick={() => setIsMobileOpen(true)}
              className="lg:hidden p-2 text-text-sub hover:text-primary hover:bg-stone-100 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
              title="Abrir menú"
            >
              <FiMenu className="text-lg" />
            </button>

            <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-background flex items-center justify-center font-sans font-bold text-xs text-primary border border-border shrink-0 select-none">
              {user?.full_name?.charAt(0) || 'A'}
            </div>
            <div className="flex flex-col leading-tight">
              <span className="text-xs sm:text-sm font-heading font-black text-text-main">
                Hola, {user?.full_name || 'Administrador'}
              </span>
              <span className="text-[9px] sm:text-[10px] text-text-sub font-semibold tracking-wide">
                Gestión de Dulce Encanto
              </span>
            </div>
          </div>
          <div className="flex items-center gap-4 sm:gap-6">
            <Link to="/" className="text-[10px] sm:text-xs text-text-sub hover:text-primary font-bold transition-colors uppercase tracking-wider">
              Ver Web
            </Link>
            <div className="flex items-center gap-1 sm:gap-2">
              <button
                onClick={toggleTheme}
                className="p-2 text-text-sub hover:text-primary hover:bg-stone-50 dark:hover:bg-stone-800 rounded-lg transition-colors flex items-center justify-center shrink-0 cursor-pointer"
                title={theme === 'dark' ? 'Modo claro' : 'Modo oscuro'}
              >
                {theme === 'dark' ? <FiSun className="text-sm text-amber-500 fill-amber-500" /> : <FiMoon className="text-sm" />}
              </button>
              <button className="p-2 text-text-sub hover:text-primary hover:bg-stone-50 dark:hover:bg-stone-800 rounded-lg transition-colors flex items-center justify-center shrink-0">
                <FiBell className="text-sm" />
              </button>
              <button className="p-2 text-text-sub hover:text-primary hover:bg-stone-50 dark:hover:bg-stone-800 rounded-lg transition-colors flex items-center justify-center shrink-0">
                <FiSettings className="text-sm" />
              </button>
            </div>
          </div>
        </header>

        {/* Outlet component */}
        <main className="flex-grow p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
          <Outlet />
        </main>
      </div>

    </div>
  )
}
