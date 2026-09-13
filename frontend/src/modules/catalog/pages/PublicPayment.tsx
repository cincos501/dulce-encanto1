import React, { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { Card, CardContent, Badge, Typography, Button } from '@/design-system'
import { FiCheckCircle, FiClock, FiAlertCircle, FiRefreshCw, FiShoppingBag, FiArrowLeft, FiMessageCircle } from 'react-icons/fi'
import axios from 'axios'

interface PaymentDetails {
  qr_id: string;
  order_id: number;
  amount: number;
  status: string;
  payment_date: string | null;
  qr_image_url: string;
  customer: {
    name: string;
    phone: string;
  };
  items: Array<{
    quantity: number;
    price: number;
    product_name: string;
    variant_name: string;
  }>;
}

export default function PublicPayment() {
  const { qrId } = useParams<{ qrId: string }>()
  const [loading, setLoading] = useState<boolean>(true)
  const [error, setError] = useState<string | null>(null)
  const [payment, setPayment] = useState<PaymentDetails | null>(null)
  const [checking, setChecking] = useState<boolean>(false)

  const fetchPaymentDetails = async (isManual = false) => {
    if (isManual) setChecking(true)
    try {
      const baseUrl = ((import.meta.env.VITE_API_URL as string) || 'http://localhost:8000').replace(/\/+$/, '')
      const response = await axios.get(`${baseUrl}/api/payments/qr/${qrId}`)
      if (response.data?.success) {
        setPayment(response.data.data)
        setError(null)
      } else {
        setError('No se pudo cargar la información del pago.')
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al conectar con el servidor de pagos.')
    } finally {
      setLoading(false)
      if (isManual) setChecking(false)
    }
  }

  // Poll for payment updates every 5 seconds if status is Pendiente
  useEffect(() => {
    fetchPaymentDetails()

    const interval = setInterval(() => {
      if (payment?.status === 'Pendiente') {
        fetchPaymentDetails()
      }
    }, 5000)

    return () => clearInterval(interval)
  }, [qrId, payment?.status])

  if (loading) {
    return (
      <div className="min-h-screen bg-stone-50 dark:bg-stone-950 flex flex-col items-center justify-center p-4 font-sans text-text-main">
        <div className="flex flex-col items-center gap-3">
          <FiRefreshCw className="animate-spin text-4xl text-primary" />
          <Typography variant="body" className="text-text-sub text-xs uppercase font-bold tracking-widest">
            Cargando detalles de pago...
          </Typography>
        </div>
      </div>
    )
  }

  if (error || !payment) {
    return (
      <div className="min-h-screen bg-stone-50 dark:bg-stone-950 flex flex-col items-center justify-center p-4 font-sans text-text-main">
        <Card className="max-w-md w-full border-red-200 dark:border-red-950/40">
          <CardContent className="p-8 text-center space-y-4">
            <FiAlertCircle className="text-5xl text-red-500 mx-auto" />
            <Typography variant="h3" className="font-heading font-black text-lg">
              Error de Pago
            </Typography>
            <p className="text-text-sub text-xs font-semibold leading-relaxed">
              {error || 'El código de pago solicitado no existe o ha expirado.'}
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

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Completado':
        return (
          <Badge variant="success" className="font-bold flex items-center gap-1 w-fit">
            <FiCheckCircle className="text-[10px]" />
            <span>Pago Confirmado</span>
          </Badge>
        )
      case 'Pendiente':
        return (
          <Badge variant="warning" className="font-bold flex items-center gap-1 w-fit">
            <FiClock className="text-[10px] animate-pulse" />
            <span>Pendiente de Pago</span>
          </Badge>
        )
      default:
        return (
          <Badge variant="danger" className="font-bold flex items-center gap-1 w-fit">
            <FiAlertCircle className="text-[10px]" />
            <span>Pago Fallido</span>
          </Badge>
        )
    }
  }

  return (
    <div className="min-h-screen bg-stone-50 dark:bg-stone-950 flex flex-col items-center justify-center p-4 md:p-8 font-sans text-text-main">
      <div className="max-w-4xl w-full space-y-6">

        {/* Navigation Back Link */}
        <Link to="/menu" className="inline-flex items-center gap-1.5 text-xs text-text-sub hover:text-primary font-bold uppercase tracking-wider transition-colors">
          <FiArrowLeft />
          <span>Volver al Catálogo</span>
        </Link>

        <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">

          {/* Main QR Card */}
          <Card className="md:col-span-7 border-border bg-white dark:bg-stone-900 shadow-xl overflow-hidden">
            <CardContent className="p-6 md:p-8 space-y-6 text-center">

              <div className="space-y-2">
                <div className="mx-auto block w-fit">
                  {getStatusBadge(payment.status)}
                </div>
                <Typography variant="h2" className="font-heading font-black text-xl md:text-2xl text-text-main">
                  Pago del Pedido #{String(payment.order_id).padStart(6, '0')}
                </Typography>
              </div>

              {payment.status === 'Pendiente' ? (
                <>
                  <div className="bg-stone-50 dark:bg-stone-950 p-4 rounded-xl border border-border inline-block max-w-[280px] w-full">
                    <img
                      src={payment.qr_image_url}
                      alt="Código QR de Pago Baneco"
                      className="w-full h-auto object-contain rounded-lg shadow-sm border border-stone-200 dark:border-stone-800"
                    />
                  </div>

                  <div className="space-y-4">
                    <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-950/40 rounded-xl p-4 text-left text-xs text-amber-800 dark:text-amber-350 leading-relaxed font-semibold">
                      <p>
                        Tu pedido fue registrado correctamente. Realiza el pago mediante el código QR de arriba.
                        Una vez confirmado el pago recibirás una notificación automática por WhatsApp.
                        También recibirás otro mensaje cuando tu pedido esté listo para ser recogido/despachado.
                      </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-center gap-3 justify-center pt-2">
                      <Button
                        onClick={() => fetchPaymentDetails(true)}
                        disabled={checking}
                        variant="secondary"
                        className="w-full sm:w-auto uppercase font-bold text-xs tracking-wider inline-flex items-center gap-2 justify-center"
                      >
                        <FiRefreshCw className={checking ? 'animate-spin' : ''} />
                        <span>{checking ? 'Verificando...' : 'Verificar Pago'}</span>
                      </Button>
                    </div>
                  </div>
                </>
              ) : payment.status === 'Completado' ? (
                <div className="py-12 px-4 space-y-4 text-center">
                  <FiCheckCircle className="text-6xl text-emerald-500 mx-auto animate-bounce" />
                  <Typography variant="h3" className="font-heading font-black text-lg text-emerald-600 dark:text-emerald-400">
                    ¡Pedido Pagado y Confirmado!
                  </Typography>
                  <p className="text-text-sub text-xs max-w-sm mx-auto font-semibold leading-relaxed">
                    Hemos recibido tu pago de forma exitosa. Tu pedido ya ingresó a la lista de producción del chef de Dulce Encanto.
                  </p>
                  
                  <div className="pt-2">
                    <a
                      href={`https://wa.me/59177872032?text=${encodeURIComponent(
                        `¡Hola Dulce Encanto! Acabo de realizar y pagar el pedido #${String(payment.order_id).padStart(6, '0')} a nombre de ${payment.customer.name} (Tel: ${payment.customer.phone}) por Bs. ${payment.amount.toFixed(2)}. Por favor revisar los detalles.`
                      )}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-lg hover:shadow-emerald-600/30 transition-all active:scale-95"
                    >
                      <FiMessageCircle className="text-base" />
                      <span>Notificar por WhatsApp</span>
                    </a>
                  </div>
                </div>
              ) : (
                <div className="py-12 px-4 space-y-4 text-center">
                  <FiAlertCircle className="text-6xl text-red-500 mx-auto" />
                  <Typography variant="h3" className="font-heading font-black text-lg text-red-600">
                    Error o Expiración de QR
                  </Typography>
                  <p className="text-text-sub text-xs max-w-sm mx-auto font-semibold leading-relaxed">
                    Este código de pago no se encuentra disponible. Por favor ponte en contacto con Dulce Encanto para generar un nuevo código.
                  </p>
                </div>
              )}

            </CardContent>
          </Card>

          {/* Sidebar details */}
          <div className="md:col-span-5 space-y-6">

            {/* Amount details */}
            <Card className="border-border bg-white dark:bg-stone-900 shadow-md">
              <CardContent className="p-6 space-y-4">
                <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Total a Pagar</span>
                <span className="font-heading font-black text-3xl text-primary block">Bs. {payment.amount.toFixed(2)}</span>

                <hr className="border-border" />

                <div className="text-xs space-y-2 font-medium">
                  <div className="flex justify-between">
                    <span className="text-text-sub font-bold">Cliente:</span>
                    <span className="text-text-main font-bold">{payment.customer.name}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-text-sub font-bold">WhatsApp:</span>
                    <span className="text-text-main font-bold">{payment.customer.phone}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Items summary */}
            <Card className="border-border bg-white dark:bg-stone-900 shadow-md">
              <CardContent className="p-6 space-y-4">
                <div className="flex items-center gap-2 text-primary font-bold text-xs uppercase tracking-wider">
                  <FiShoppingBag />
                  <span>Productos del Pedido</span>
                </div>

                <div className="divide-y divide-border overflow-hidden rounded-lg border border-border">
                  {payment.items.map((item, idx) => (
                    <div key={idx} className="p-3 bg-stone-50/30 dark:bg-stone-950/20 text-xs flex justify-between gap-4">
                      <div>
                        <span className="font-bold text-text-main">{item.quantity}x {item.product_name}</span>
                        <span className="text-[10px] text-text-sub block italic">{item.variant_name}</span>
                      </div>
                      <span className="font-bold text-primary">Bs. {(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

          </div>

        </div>

      </div>
    </div>
  )
}
