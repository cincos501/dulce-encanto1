import React from 'react'
import { useAuth } from '@/app/providers/AuthContext'
import { useQuery } from '@tanstack/react-query'
import { 
  StatCard, 
  Card, 
  CardHeader, 
  CardContent, 
  Badge, 
  Typography 
} from '@/design-system'
import { 
  FiDollarSign, 
  FiShoppingBag, 
  FiPackage, 
  FiSliders, 
  FiShield, 
  FiTrendingUp, 
  FiAward, 
  FiThumbsUp, 
  FiUser 
} from 'react-icons/fi'
import productsService from '@/shared/services/productsService'
import usersService from '@/shared/services/usersService'
import ordersService from '@/shared/services/ordersService'
import reportsService from '@/shared/services/reportsService'

export default function Dashboard() {
  const { user } = useAuth()

  const today = new Date().toISOString().split('T')[0]
  const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]

  // Query summary metrics from DB Views
  const { data: summaryRes } = useQuery({
    queryKey: ['dashboard-summary-metrics', firstDayOfMonth, today],
    queryFn: async () => {
      const response = await reportsService.getSummary(firstDayOfMonth, today)
      return response.data?.data
    },
    enabled: !!user
  })

  // Query top products from vw_most_sold_products
  const { data: topProductsRes = [] } = useQuery({
    queryKey: ['dashboard-top-products', firstDayOfMonth, today],
    queryFn: async () => {
      const response = await reportsService.getProducts(firstDayOfMonth, today)
      return response.data?.data || []
    },
    enabled: !!user
  })

  // Query recent orders
  const { data: recentOrdersRes = [] } = useQuery({
    queryKey: ['dashboard-recent-orders'],
    queryFn: async () => {
      const response = await ordersService.paginate(1, '', 5)
      return response.data?.data || []
    },
    enabled: !!user
  })

  // Queries to load recent products & users
  const { data: recentProducts = [] } = useQuery({
    queryKey: ['recent-products-dashboard'],
    queryFn: async () => {
      const response = await productsService.paginate(1, '', 5)
      return response.data?.data || []
    },
    enabled: !!user
  })

  const { data: recentUsers = [] } = useQuery({
    queryKey: ['recent-users-dashboard'],
    queryFn: async () => {
      const response = await usersService.paginate(1, '', 5)
      return response.data?.data || []
    },
    enabled: !!user
  })

  if (!user) return null

  const summary = summaryRes || {
    period_sales: 0,
    registered_orders: 0,
    products_sold: 0,
    critical_stock: 0,
    pending_orders: 0
  }

  // Dynamic stats calculated from SQL Views
  const stats = [
    { 
      title: 'Ventas del Mes', 
      value: `Bs. ${Number(summary.period_sales || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, 
      change: 'Calculado desde vistas SQL', 
      changeType: 'increase' as const, 
      icon: <FiDollarSign /> 
    },
    { 
      title: 'Pedidos Registrados', 
      value: `${summary.registered_orders || 0}`, 
      change: `${summary.pending_orders || 0} pendientes`, 
      changeType: 'increase' as const, 
      icon: <FiShoppingBag /> 
    },
    { 
      title: 'Productos Vendidos', 
      value: `${summary.products_sold || 0} unidades`, 
      change: 'Demanda del mes', 
      changeType: 'neutral' as const, 
      icon: <FiPackage /> 
    },
    { 
      title: 'Insumos Críticos', 
      value: `${summary.critical_stock || 0} insumos`, 
      change: summary.critical_stock > 0 ? 'Requiere reabastecimiento' : 'Stock saludable', 
      changeType: summary.critical_stock > 0 ? ('decrease' as const) : ('neutral' as const), 
      icon: <FiSliders /> 
    }
  ]

  return (
    <div className="space-y-8 animate-fade-in font-sans">
      
      {/* WELCOME SECTION */}
      <div className="bg-surface border border-border rounded-lg p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
        <div className="space-y-2 text-center md:text-left">
          <Typography variant="h1" className="text-3xl font-black text-primary">
            ¡Hola, {user.full_name}!
          </Typography>
          <p className="text-text-sub text-xs max-w-xl leading-relaxed font-semibold">
            Te damos la bienvenida al panel administrativo de Dulce Encanto. Gestiona de manera unificada el catálogo, pedidos, personal y promociones de la pastelería.
          </p>
        </div>
        <div className="flex gap-2">
          {user.roles && user.roles.map((role) => (
            <Badge key={role} variant="info" className="py-1.5 px-4 text-xs font-bold shadow-sm flex items-center gap-1.5">
              <FiShield />
              <span>{role}</span>
            </Badge>
          ))}
        </div>
      </div>

      {/* STATS SECTION */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((s, idx) => (
          <StatCard
            key={idx}
            title={s.title}
            value={s.value}
            change={s.change}
            changeType={s.changeType}
            icon={s.icon}
          />
        ))}
      </div>

      {/* BUSINESS OVERVIEW GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* RECENT ORDERS */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiShoppingBag className="text-lg text-primary" />
              <Typography variant="h3">Pedidos Recientes</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Monitorea y atiende el estado de los últimos pedidos ingresados.</p>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-xs">
                <thead>
                  <tr className="bg-background border-b border-border/80 text-text-sub text-[10px] uppercase font-bold tracking-widest">
                    <th className="px-6 py-3.5">Código</th>
                    <th className="px-6 py-3.5">Cliente</th>
                    <th className="px-6 py-3.5">Total</th>
                    <th className="px-6 py-3.5 text-center">Estado</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/40 text-text-main font-medium">
                  {recentOrdersRes.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-6 py-8 text-center text-text-sub/50 italic">
                        No hay pedidos registrados recientemente.
                      </td>
                    </tr>
                  ) : (
                    recentOrdersRes.map((o: any) => (
                      <tr key={o.id} className="hover:bg-stone-50/50 transition-colors">
                        <td className="px-6 py-4 font-mono font-bold text-text-sub/70">#{o.id}</td>
                        <td className="px-6 py-4 font-bold text-primary">{o.customer?.full_name || 'Cliente Anónimo'}</td>
                        <td className="px-6 py-4 font-bold text-primary">Bs. {Number(o.total || 0).toFixed(2)}</td>
                        <td className="px-6 py-4 text-center">
                          <Badge 
                            variant={
                              o.status === 'Entregado' ? 'success' : o.status === 'En preparación' ? 'warning' : 'neutral'
                            }
                          >
                            {o.status}
                          </Badge>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        {/* TOP SELLING PRODUCTS */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiTrendingUp className="text-lg text-primary" />
              <Typography variant="h3">Más Vendidos (Vistas SQL)</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Productos con mayor demanda comercial del mes.</p>
          </CardHeader>
          <CardContent className="space-y-4">
            {topProductsRes.length === 0 ? (
              <p className="p-4 text-center text-text-sub/50 italic text-xs">No hay datos de ventas en este período.</p>
            ) : (
              topProductsRes.slice(0, 4).map((p: any, idx: number) => (
                <div key={idx} className="flex items-center justify-between gap-4 p-3.5 rounded-lg bg-background border border-border/80">
                  <div className="space-y-0.5 flex flex-col">
                    <span className="font-bold text-primary text-xs flex items-center gap-1.5">
                      {idx === 0 ? <FiTrendingUp className="text-amber-500" /> : idx === 1 ? <FiAward className="text-amber-500" /> : <FiThumbsUp className="text-amber-500" />}
                      <span>{p.product_name}</span>
                    </span>
                    <span className="text-[9px] text-text-sub/70 font-bold uppercase tracking-widest pl-5">{p.variant_name}</span>
                  </div>
                  <div className="text-right">
                    <span className="font-bold text-primary block text-xs">Bs. {Number(p.total_generated || 0).toFixed(2)}</span>
                    <span className="text-[9px] text-text-sub font-bold">{p.quantity_sold} vendidos</span>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

      </div>

      {/* RECENT RECORDS FEEDS */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        {/* RECENT PRODUCTS */}
        <Card className="md:col-span-1">
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiPackage className="text-lg text-primary" />
              <Typography variant="h3">Últimos Productos</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Productos añadidos recientemente al catálogo.</p>
          </CardHeader>
          <CardContent className="p-0">
            <div className="divide-y divide-border/40 text-xs">
              {recentProducts.length === 0 ? (
                <p className="p-4 text-center text-text-sub/40 italic">No hay productos recientes.</p>
              ) : (
                recentProducts.map((p: any) => (
                  <div key={p.id} className="px-5 py-3 hover:bg-stone-50/50 transition-colors flex items-center justify-between">
                    <div className="flex flex-col gap-0.5">
                      <span className="font-bold text-primary">{p.name}</span>
                      <span className="text-[9px] text-text-sub font-semibold">{p.category?.name || 'Sin categoría'}</span>
                    </div>
                    <Badge variant={p.is_active ? 'success' : 'neutral'}>
                      {p.is_active ? 'Activo' : 'Inactivo'}
                    </Badge>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

        {/* RECENT USERS */}
        <Card className="md:col-span-1">
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiUser className="text-lg text-primary" />
              <Typography variant="h3">Personal Reciente</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Últimos integrantes creados en el local.</p>
          </CardHeader>
          <CardContent className="p-0">
            <div className="divide-y divide-border/40 text-xs">
              {recentUsers.length === 0 ? (
                <p className="p-4 text-center text-text-sub/40 italic">No hay integrantes recientes.</p>
              ) : (
                recentUsers.map((u: any) => (
                  <div key={u.id} className="px-5 py-3 hover:bg-stone-50/50 transition-colors flex items-center justify-between">
                    <div className="flex flex-col gap-0.5">
                      <span className="font-bold text-primary">{u.full_name}</span>
                      <span className="text-[9px] text-text-sub font-semibold">{u.roles?.[0] || 'Personal'}</span>
                    </div>
                    <Badge variant={u.is_active ? 'success' : 'neutral'}>
                      {u.is_active ? 'Activo' : 'Inactivo'}
                    </Badge>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>

        {/* ACCOUNT INFO CARD */}
        <Card className="md:col-span-1">
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiUser className="text-lg text-primary" />
              <Typography variant="h3">Datos de Cuenta</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Información básica del perfil autenticado.</p>
          </CardHeader>
          <CardContent className="space-y-4 text-xs font-medium">
            <div>
              <span className="text-text-sub block text-[9px] uppercase font-bold tracking-widest">Nombre Completo</span>
              <span className="text-primary font-bold text-xs">{user.full_name}</span>
            </div>
            <div>
              <span className="text-text-sub block text-[9px] uppercase font-bold tracking-widest">Correo Electrónico</span>
              <span className="text-primary font-bold text-xs">{user.email}</span>
            </div>
            <div>
              <span className="text-text-sub block text-[9px] uppercase font-bold tracking-widest">Teléfono</span>
              <span className="text-primary font-bold text-xs">{user.phone || 'No registrado'}</span>
            </div>
          </CardContent>
        </Card>

      </div>

    </div>
  )
}
