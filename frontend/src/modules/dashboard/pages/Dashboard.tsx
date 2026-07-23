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

export default function Dashboard() {
  const { user } = useAuth()

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

  // Simulated metrics
  const stats = [
    { title: 'Ventas del Mes', value: 'Bs. 1,480.00', change: '+12.5% vs mes anterior', changeType: 'increase' as const, icon: <FiDollarSign /> },
    { title: 'Pedidos Activos', value: '16', change: '+4 nuevos hoy', changeType: 'increase' as const, icon: <FiShoppingBag /> },
    { title: 'Productos en Menú', value: '12', change: 'Estructura unificada', changeType: 'neutral' as const, icon: <FiPackage /> },
    { title: 'Ingredientes Adicionales', value: '8 extras', change: 'Disponibles', changeType: 'neutral' as const, icon: <FiSliders /> }
  ]

  const recentOrders = [
    { id: '#1004', customer: 'Andrea Rojas', items: 'Torta Tres Leches (Grande)', total: 'Bs. 24.50', status: 'Entregado', time: 'Hace 20 min' },
    { id: '#1003', customer: 'Carlos Mendoza', items: 'Cheesecake de Oreo (Personal)', total: 'Bs. 6.00', status: 'Preparando', time: 'Hace 45 min' },
    { id: '#1002', customer: 'Laura Silva', items: 'Budín de Limón + Velitas', total: 'Bs. 14.00', status: 'Pendiente', time: 'Hace 1 hora' }
  ]

  const topProducts = [
    { name: 'Torta Selva Negra', orders: '32 pedidos', revenue: 'Bs. 640.00', popularity: 'Muy alta', icon: <FiTrendingUp className="text-amber-500" /> },
    { name: 'Torta Tres Leches', orders: '28 pedidos', revenue: 'Bs. 545.00', popularity: 'Alta', icon: <FiAward className="text-amber-500" /> },
    { name: 'Brazo Gitano Chocolate', orders: '15 pedidos', revenue: 'Bs. 225.00', popularity: 'Media', icon: <FiThumbsUp className="text-amber-500" /> }
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
                    <th className="px-6 py-3.5">Detalle</th>
                    <th className="px-6 py-3.5 text-center">Total</th>
                    <th className="px-6 py-3.5 text-center">Estado</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/40 text-text-main font-medium">
                  {recentOrders.map((o) => (
                    <tr key={o.id} className="hover:bg-stone-50/50 transition-colors">
                      <td className="px-6 py-4 font-mono font-bold text-text-sub/70">{o.id}</td>
                      <td className="px-6 py-4 font-bold text-primary">{o.customer}</td>
                      <td className="px-6 py-4 text-[10px] text-text-sub font-semibold">{o.items}</td>
                      <td className="px-6 py-4 text-center font-bold text-primary">{o.total}</td>
                      <td className="px-6 py-4 text-center">
                        <Badge 
                          variant={
                            o.status === 'Entregado' ? 'success' : o.status === 'Preparando' ? 'warning' : 'neutral'
                          }
                        >
                          {o.status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
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
              <Typography variant="h3">Más Vendidos</Typography>
            </div>
            <p className="text-text-sub/70 text-[10px] mt-0.5 font-bold uppercase tracking-wider">Productos con mayor demanda comercial del mes.</p>
          </CardHeader>
          <CardContent className="space-y-4">
            {topProducts.map((p, idx) => (
              <div key={idx} className="flex items-center justify-between gap-4 p-3.5 rounded-lg bg-background border border-border/80">
                <div className="space-y-0.5 flex flex-col">
                  <span className="font-bold text-primary text-xs flex items-center gap-1.5">
                    {p.icon}
                    <span>{p.name}</span>
                  </span>
                  <span className="text-[9px] text-text-sub/70 font-bold uppercase tracking-widest pl-5">{p.popularity}</span>
                </div>
                <div className="text-right">
                  <span className="font-bold text-primary block text-xs">{p.revenue}</span>
                  <span className="text-[9px] text-text-sub font-bold">{p.orders}</span>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

      </div>

      {/* RECENT RECORDS FEEDS */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        {/* RECENT PRODUCTS */}
        <Card className="md:col-span-1">
          <CardHeader>
            <div className="flex items-center gap-2">
              <FiPackage className="text-lg text-primary animate-bounce" />
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
