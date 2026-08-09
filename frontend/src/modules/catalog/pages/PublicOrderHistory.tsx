import React, { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { Card, CardContent, Badge, Typography, Button } from '@/design-system'
import { FiClock, FiCheckCircle, FiAlertCircle, FiChevronDown, FiChevronUp, FiShoppingBag, FiCreditCard, FiTrendingUp, FiArrowLeft, FiLogOut } from 'react-icons/fi'
import axios from 'axios'

interface OrderHistoryItem {
  id: number;
  status: string;
  production_stage: string;
  total: number;
  paid_amount: number;
  balance_pending: number;
  qr_id: string | null;
  delivery_date: string | null;
  created_at: string;
  items: Array<{
    quantity: number;
    price: number;
    product_name: string;
    variant_name: string;
  }>;
}

interface CustomerDetails {
  name: string;
  phone: string;
}

export default function PublicOrderHistory() {
  const { phoneToken } = useParams<{ phoneToken: string }>()
  const [loading, setLoading] = useState<boolean>(true)
  const [error, setError] = useState<string | null>(null)
  const [customer, setCustomer] = useState<CustomerDetails | null>(null)
  const [orders, setOrders] = useState<OrderHistoryItem[]>([])
  const [expandedOrderId, setExpandedOrderId] = useState<number | null>(null)

  const fetchHistory = async () => {
    try {
      const response = await axios.get(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'}/orders/history/${phoneToken}`)
      if (response.data?.success) {
        setCustomer(response.data.data.customer)
        setOrders(response.data.data.orders || [])
        setError(null)
      } else {
        setError('No se pudo cargar el historial de pedidos.')
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al conectar con el servidor.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchHistory()
  }, [phoneToken])

  const toggleExpandOrder = (id: number) => {
    setExpandedOrderId(prev => (prev === id ? null : id))
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-stone-50 dark:bg-stone-950 flex flex-col items-center justify-center p-4 font-sans text-text-main">
        <div className="flex flex-col items-center gap-3">
          <div className="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin" />
          <Typography variant="body" className="text-text-sub text-xs uppercase font-bold tracking-widest">
            Cargando historial de pedidos...
          </Typography>
        </div>
      </div>
    )
  }

  if (error || !customer) {
    return (
      <div className="min-h-screen bg-stone-50 dark:bg-stone-950 flex flex-col items-center justify-center p-4 font-sans text-text-main">
        <Card className="max-w-md w-full border-red-250">
          <CardContent className="p-8 text-center space-y-4">
            <FiAlertCircle className="text-5xl text-red-500 mx-auto" />
            <Typography variant="h3" className="font-heading font-black text-lg">
              Historial de Pedidos
            </Typography>
            <p className="text-text-sub text-xs font-semibold leading-relaxed">
              El enlace de consulta ha expirado o no es válido. Escribe "Mis pedidos" al chatbot de WhatsApp para obtener un enlace actualizado.
            </p>
            <div className="pt-4">
              <Link to="/menu">
                <Button className="w-full uppercase font-bold text-xs tracking-wider">
                  Volver al Catálogo
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  const getOrderStatusBadge = (status: string) => {
    switch (status) {
      case 'Pendiente':
        return <Badge variant="warning" className="font-bold">Pendiente de Pago</Badge>
      case 'Confirmado':
        return <Badge variant="neutral" className="font-bold">Confirmado</Badge>
      case 'En preparación':
        return <Badge variant="info" className="font-bold">En Preparación</Badge>
      case 'Listo':
        return <Badge variant="success" className="font-bold">Listo para Recoger</Badge>
      case 'Entregado':
        return <Badge variant="success" className="font-bold">Entregado</Badge>
      default:
        return <Badge variant="danger" className="font-bold">Cancelado</Badge>
    }
  }

  return (
    <div className="min-h-screen bg-stone-50 dark:bg-stone-950 p-4 md:p-8 font-sans text-text-main">
      <div className="max-w-4xl mx-auto space-y-6">
        
        {/* Navigation header */}
        <div className="flex items-center justify-between">
          <Link to="/menu" className="inline-flex items-center gap-1.5 text-xs text-text-sub hover:text-primary font-bold uppercase tracking-wider transition-colors">
            <FiArrowLeft />
            <span>Volver al Catálogo</span>
          </Link>
          
          <span className="text-[10px] bg-stone-200/50 dark:bg-stone-900 text-text-sub font-bold px-3 py-1.5 rounded-full">
            Sesión Protegida
          </span>
        </div>

        {/* Customer Header */}
        <div className="bg-white dark:bg-stone-900 border border-border rounded-2xl p-6 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="space-y-1.5">
            <span className="text-[10px] text-primary font-bold uppercase tracking-widest block">Historial de Pedidos</span>
            <Typography variant="h1" className="font-heading font-black text-2xl">
              ¡Hola, {customer.name}!
            </Typography>
            <p className="text-text-sub text-xs font-semibold">
              Aquí puedes revisar todos los pedidos asociados a tu WhatsApp: <strong className="text-text-main">{customer.phone}</strong>.
            </p>
          </div>
          
          <div className="text-left md:text-right">
            <span className="text-[10px] text-text-sub font-bold uppercase block">Total de pedidos</span>
            <span className="text-2xl font-heading font-black text-primary">{orders.length}</span>
          </div>
        </div>

        {/* Orders list */}
        {orders.length === 0 ? (
          <Card className="border-border">
            <CardContent className="p-12 text-center space-y-3">
              <FiShoppingBag className="text-5xl text-text-sub/40 mx-auto" />
              <Typography variant="h3" className="font-heading font-black text-base">
                No tienes pedidos registrados
              </Typography>
              <p className="text-text-sub text-xs">
                ¡Empieza a explorar nuestro delicioso catálogo de repostería y realiza tu primera compra!
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-4">
            {orders.map((order) => {
              const isExpanded = expandedOrderId === order.id;

              return (
                <Card 
                  key={order.id} 
                  className={`border-border hover:shadow-lg transition-all ${
                    isExpanded ? 'ring-1 ring-primary/45 bg-stone-50/20' : 'bg-white dark:bg-stone-900'
                  }`}
                >
                  <CardContent className="p-5 md:p-6 space-y-4">
                    
                    {/* Collapsed top bar */}
                    <div 
                      onClick={() => toggleExpandOrder(order.id)} 
                      className="flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer select-none"
                    >
                      <div className="flex flex-wrap items-center gap-3">
                        <span className="font-mono font-bold text-xs text-text-sub">
                          #{String(order.id).padStart(6, '0')}
                        </span>
                        
                        {getOrderStatusBadge(order.status)}

                        {order.status !== 'Listo' && order.status !== 'Entregado' && order.status !== 'Cancelado' && order.status !== 'Pendiente' && (
                          <span className="text-[10px] bg-primary/10 dark:bg-primary/20 text-primary font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">
                            Fase: {order.production_stage}
                          </span>
                        )}
                      </div>

                      <div className="flex items-center justify-between md:justify-end gap-6">
                        <div className="text-right">
                          <span className="text-[9px] text-text-sub font-bold block uppercase">Total</span>
                          <span className="font-bold text-sm text-primary">Bs. {order.total.toFixed(2)}</span>
                        </div>
                        <div className="text-right hidden sm:block">
                          <span className="text-[9px] text-text-sub font-bold block uppercase">Fecha</span>
                          <span className="text-xs font-semibold text-text-main">
                            {new Date(order.created_at).toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })}
                          </span>
                        </div>
                        
                        <div>
                          {expandedOrderId === order.id ? <FiChevronUp className="text-text-sub" /> : <FiChevronDown className="text-text-sub" />}
                        </div>
                      </div>
                    </div>

                    {/* Expanded details section */}
                    {expandedOrderId === order.id && (
                      <div className="pt-4 border-t border-border space-y-5 animate-slide-down">
                        
                        {/* Summary metrics */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                          <div className="bg-stone-50 dark:bg-stone-950/45 p-3 rounded-lg border border-border">
                            <span className="text-[9px] text-text-sub font-bold uppercase tracking-wider block">Pagado</span>
                            <span className="font-bold text-xs text-emerald-600 dark:text-emerald-400">Bs. {order.paid_amount.toFixed(2)}</span>
                          </div>
                          <div className="bg-stone-50 dark:bg-stone-950/45 p-3 rounded-lg border border-border">
                            <span className="text-[9px] text-text-sub font-bold uppercase tracking-wider block">Saldo Pendiente</span>
                            <span className="font-bold text-xs text-amber-600 dark:text-amber-400">Bs. {order.balance_pending.toFixed(2)}</span>
                          </div>
                          <div className="bg-stone-50 dark:bg-stone-950/45 p-3 rounded-lg border border-border">
                            <span className="text-[9px] text-text-sub font-bold uppercase tracking-wider block">Fecha de Entrega</span>
                            <span className="font-bold text-xs text-text-main">
                              {order.delivery_date 
                                ? new Date(order.delivery_date).toLocaleString('es-CL', { dateStyle: 'short', timeStyle: 'short' }) 
                                : 'No especificada'}
                            </span>
                          </div>
                        </div>

                        {/* Items list */}
                        <div className="space-y-2">
                          <div className="flex items-center gap-1.5 text-[10px] text-text-sub font-bold uppercase tracking-wider">
                            <FiShoppingBag />
                            <span>Detalle de Productos</span>
                          </div>
                          <div className="border border-border rounded-lg overflow-hidden divide-y divide-border">
                            {order.items.map((item, idx) => (
                              <div key={idx} className="p-3 bg-white dark:bg-stone-900 flex items-center justify-between text-xs font-medium">
                                <div>
                                  <span className="font-bold text-text-main">{item.quantity}x {item.product_name}</span>
                                  <span className="text-[10px] text-text-sub block italic">{item.variant_name}</span>
                                </div>
                                <span className="font-bold text-primary">Bs. {(item.price * item.quantity).toFixed(2)}</span>
                              </div>
                            ))}
                          </div>
                        </div>

                        {/* QR Payment Link button if pending */}
                        {order.status === 'Pendiente' && order.qr_id && (
                          <div className="bg-amber-50 dark:bg-amber-950/15 border border-amber-250 p-4 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div className="text-xs space-y-1">
                              <span className="font-bold text-amber-850 dark:text-amber-450 block">Pendiente de confirmación</span>
                              <p className="text-amber-800 dark:text-amber-350">
                                Para completar tu compra y confirmar el pedido, realiza el pago del código QR.
                              </p>
                            </div>
                            <Link to={`/payment/${order.qr_id}`} className="w-full sm:w-auto">
                              <Button className="w-full bg-amber-600 border-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider">
                                Pagar con QR
                              </Button>
                            </Link>
                          </div>
                        )}

                        {/* Production details */}
                        {order.status !== 'Pendiente' && order.status !== 'Cancelado' && (
                          <div className="bg-stone-50 dark:bg-stone-950/20 border border-border p-4 rounded-xl space-y-3">
                            <div className="flex items-center gap-1.5 text-[10px] text-text-sub font-bold uppercase tracking-wider">
                              <FiTrendingUp />
                              <span>Seguimiento de Producción</span>
                            </div>
                            
                            {/* Horizontal progress steps */}
                            <div className="grid grid-cols-6 gap-1 text-center font-bold text-[8px] sm:text-[9px] uppercase tracking-wider">
                              {['Programado', 'Preparación', 'Horneado', 'Decoración', 'Empaque', 'Listo'].map((stage, idx) => {
                                const stagesOrder = ['Programado', 'Preparación', 'Horneado', 'Decoración', 'Empaque', 'Listo'];
                                const currentIdx = stagesOrder.indexOf(order.production_stage);
                                const itemIdx = stagesOrder.indexOf(stage);
                                
                                const isCompleted = itemIdx < currentIdx || order.status === 'Listo' || order.status === 'Entregado';
                                const isActive = itemIdx === currentIdx && order.status !== 'Listo' && order.status !== 'Entregado';

                                return (
                                  <div key={idx} className="space-y-1.5">
                                    <div className={`h-1.5 rounded-full ${
                                      isCompleted ? 'bg-emerald-500' : isActive ? 'bg-primary animate-pulse' : 'bg-stone-250 dark:bg-stone-800'
                                    }`} />
                                    <span className={isCompleted ? 'text-emerald-600 dark:text-emerald-400' : isActive ? 'text-primary' : 'text-text-sub/50'}>
                                      {stage}
                                    </span>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        )}

                      </div>
                    )}

                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}

      </div>
    </div>
  )
}
