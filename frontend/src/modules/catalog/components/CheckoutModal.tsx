import React, { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { 
  Button, 
  Input, 
  Textarea, 
  Badge, 
  Typography, 
  Modal 
} from '@/design-system'
import { useCart } from '@/app/providers/CartContext'
import ordersService from '@/shared/services/ordersService'
import { FiTrash2, FiPlus, FiMinus, FiMapPin, FiClock, FiShoppingBag, FiUser, FiInfo, FiCheck } from 'react-icons/fi'
import productPlaceholder from '@/assets/placeholders/product-placeholder.webp'
import { handleApiError } from '@/shared/utils/formErrors'

interface CheckoutModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const checkoutSchema = z.object({
  customer_name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'El nombre es muy largo.'),
  customer_phone: z.string().min(8, 'El teléfono debe tener al menos 8 dígitos.').max(20, 'El teléfono es muy largo.'),
  delivery_type: z.enum(['Retiro en tienda', 'Delivery']),
  address: z.string().optional(),
  observations: z.string().optional(),
  delivery_date: z.string().min(1, 'La fecha de entrega es requerida.'),
  delivery_time: z.string().min(1, 'La hora de entrega es requerida.')
}).refine((data) => {
  if (data.delivery_type === 'Delivery' && (!data.address || data.address.trim() === '')) {
    return false
  }
  return true
}, {
  message: 'La dirección es obligatoria para despachos a domicilio.',
  path: ['address']
})

type CheckoutFormInputs = z.infer<typeof checkoutSchema>

export default function CheckoutModal({ isOpen, onClose }: CheckoutModalProps) {
  const { cartItems, cartSubtotal, updateQuantity, removeFromCart, clearCart } = useCart()
  const [step, setStep] = useState<number>(1) // 1: Productos, 2: Datos, 3: Confirmación
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)
  const [submitError, setSubmitError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    watch,
    setError,
    formState: { errors },
    trigger
  } = useForm<CheckoutFormInputs>({
    resolver: zodResolver(checkoutSchema),
    defaultValues: {
      delivery_type: 'Retiro en tienda',
      address: '',
      observations: '',
      delivery_date: new Date().toISOString().split('T')[0],
      delivery_time: '12:00'
    }
  })

  const deliveryType = watch('delivery_type')
  const customerName = watch('customer_name')
  const customerPhone = watch('customer_phone')
  const address = watch('address')
  const observations = watch('observations')
  const deliveryDate = watch('delivery_date')
  const deliveryTime = watch('delivery_time')

  const handleNextToStep2 = () => {
    if (cartItems.length === 0) return
    setStep(2)
  }

  const handleNextToStep3 = async () => {
    // Validate Step 2 fields manually before proceeding to confirmation
    const isValid = await trigger([
      'customer_name',
      'customer_phone',
      'delivery_type',
      'address',
      'delivery_date',
      'delivery_time'
    ])
    if (isValid) {
      setStep(3)
    }
  }

  const onSubmit = async (data: CheckoutFormInputs) => {
    setIsSubmitting(true)
    setSubmitError(null)

    try {
      const payload = {
        customer_name: data.customer_name,
        customer_phone: data.customer_phone,
        delivery_type: data.delivery_type,
        address: data.delivery_type === 'Delivery' ? data.address : null,
        observations: data.observations || null,
        delivery_date: data.delivery_date,
        delivery_time: data.delivery_time,
        items: cartItems.map(item => ({
          product_variant_id: item.product_variant_id,
          quantity: item.quantity,
          extras: item.extras.map(e => e.id)
        }))
      }

      const response = await ordersService.checkout(payload)
      const order = response.data?.data

      if (order) {
        // Clear cart and generate whatsapp link
        clearCart()

        // Construct message
        const orderId = String(order.id).padStart(6, '0')
        const itemsText = cartItems.map(item => {
          const extrasStr = item.extras.length > 0 ? ` (+${item.extras.map(e => e.name).join(', ')})` : ''
          return `- ${item.quantity}x ${item.product_name} (${item.variant_name})${extrasStr}`
        }).join('\n')

        const message = `Hola.\nAcabo de realizar el pedido Nº ${orderId}.\n\n*Resumen del Pedido:*\n${itemsText}\n\n*Total:* $${cartSubtotal.toFixed(2)}\n*Tipo de entrega:* ${data.delivery_type}${data.delivery_type === 'Delivery' ? `\n*Dirección:* ${data.address}` : ''}\n\nMi nombre es ${data.customer_name}.\nMuchas gracias.`

        const encodedText = encodeURIComponent(message)
        // Chilean support WhatsApp ( Chile country code +56 )
        const whatsappUrl = `https://wa.me/56912345678?text=${encodedText}`

        window.open(whatsappUrl, '_blank')
        onClose()
      }
    } catch (err: any) {
      if (err.response?.status === 422) {
        handleApiError(err, setError, 'Corrija los campos señalados.')
      } else {
        setSubmitError(err.response?.data?.message || 'Error al procesar el pedido.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Finalizar Pedido — Dulce Encanto"
      maxWidthClassName="max-w-4xl"
    >
      <div className="space-y-6 font-sans text-text-main">
        {/* STEPPER HEADER */}
        <div className="flex items-center justify-center gap-2 border-b border-border pb-4 select-none">
          <div className="flex items-center gap-1.5">
            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
              step >= 1 ? 'bg-primary text-primary-foreground' : 'bg-stone-200 text-stone-600'
            }`}>
              1
            </span>
            <span className={`text-[10px] uppercase font-bold tracking-wider ${
              step === 1 ? 'text-primary' : 'text-text-sub'
            }`}>
              Productos
            </span>
          </div>
          <span className="h-px bg-stone-200 w-10" />
          <div className="flex items-center gap-1.5">
            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
              step >= 2 ? 'bg-primary text-primary-foreground' : 'bg-stone-200 text-stone-600'
            }`}>
              2
            </span>
            <span className={`text-[10px] uppercase font-bold tracking-wider ${
              step === 2 ? 'text-primary' : 'text-text-sub'
            }`}>
              Datos de Entrega
            </span>
          </div>
          <span className="h-px bg-stone-200 w-10" />
          <div className="flex items-center gap-1.5">
            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
              step >= 3 ? 'bg-primary text-primary-foreground' : 'bg-stone-200 text-stone-600'
            }`}>
              3
            </span>
            <span className={`text-[10px] uppercase font-bold tracking-wider ${
              step === 3 ? 'text-primary' : 'text-text-sub'
            }`}>
              Confirmación
            </span>
          </div>
        </div>

        {/* STEP 1: PRODUCTS IN CART */}
        {step === 1 && (
          <div className="space-y-6">
            <div className="flex items-center gap-2">
              <FiShoppingBag className="text-primary text-sm" />
              <Typography variant="h4" className="font-heading font-black text-sm text-text-main">
                Revisa tus productos seleccionados
              </Typography>
            </div>

            {cartItems.length === 0 ? (
              <div className="py-16 text-center space-y-4 flex flex-col items-center">
                <FiShoppingBag className="text-5xl text-text-sub/40" />
                <h4 className="font-heading font-black text-sm text-text-main">Tu carrito está vacío</h4>
                <p className="text-text-sub text-xs">Agrega alguna de nuestras delicias de repostería del catálogo.</p>
                <Button variant="secondary" onClick={onClose} className="text-xs uppercase font-bold tracking-wider">
                  Seguir Comprando
                </Button>
              </div>
            ) : (
              <>
                <div className="border border-border rounded-lg overflow-hidden bg-background max-h-[300px] overflow-y-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="bg-stone-50/80 dark:bg-stone-900/80 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                        <th className="p-3">Descripción</th>
                        <th className="p-3 text-center">Cantidad</th>
                        <th className="p-3 text-right">Precio Unit.</th>
                        <th className="p-3 text-right">Subtotal</th>
                        <th className="p-3 text-center w-12">Eliminar</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border text-xs">
                      {cartItems.map((item) => {
                        const unitPrice = (item.promo_price !== undefined && item.promo_price !== null ? item.promo_price : item.base_price) +
                          item.extras.reduce((sum, e) => sum + Number(e.price), 0)
                        const itemSubtotal = unitPrice * item.quantity

                        return (
                          <tr key={item.id} className="hover:bg-stone-50/30 dark:hover:bg-stone-850/30 transition-colors">
                            <td className="p-3">
                              <div className="flex items-center gap-3">
                                {item.image_url ? (
                                  <img 
                                    src={item.image_url} 
                                    alt={item.product_name} 
                                    className="w-10 h-10 object-cover rounded-md border border-border"
                                  />
                                ) : (
                                  <img 
                                    src={productPlaceholder} 
                                    alt={item.product_name} 
                                    className="w-10 h-10 object-cover rounded-md border border-border"
                                  />
                                )}
                                <div>
                                  <div className="font-bold text-text-main">{item.product_name}</div>
                                  <div className="text-[10px] text-text-sub font-semibold italic">{item.variant_name}</div>
                                  {item.extras.length > 0 && (
                                    <div className="text-[9px] text-text-sub/80 font-semibold mt-0.5">
                                      + {item.extras.map(e => e.name).join(', ')}
                                    </div>
                                  )}
                                </div>
                              </div>
                            </td>
                            <td className="p-3">
                              <div className="flex items-center justify-center gap-2 bg-stone-50 dark:bg-stone-900 border border-border rounded-lg w-fit mx-auto px-1">
                                <button
                                  type="button"
                                  onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                  className="p-1 text-text-sub hover:text-primary transition-colors cursor-pointer"
                                >
                                  <FiMinus className="text-[10px]" />
                                </button>
                                <span className="font-bold text-xs text-text-main w-6 text-center">{item.quantity}</span>
                                <button
                                  type="button"
                                  onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                  className="p-1 text-text-sub hover:text-primary transition-colors cursor-pointer"
                                >
                                  <FiPlus className="text-[10px]" />
                                </button>
                              </div>
                            </td>
                            <td className="p-3 text-right font-semibold text-text-sub">Bs. {unitPrice.toFixed(2)}</td>
                            <td className="p-3 text-right font-bold text-primary">Bs. {itemSubtotal.toFixed(2)}</td>
                            <td className="p-3 text-center">
                              <button
                                type="button"
                                onClick={() => removeFromCart(item.id)}
                                className="p-1.5 text-text-sub/50 hover:text-red-650 transition-colors rounded-lg hover:bg-red-50 cursor-pointer mx-auto block"
                              >
                                <FiTrash2 className="text-xs" />
                              </button>
                            </td>
                          </tr>
                        )
                      })}
                    </tbody>
                  </table>
                </div>

                <div className="flex flex-col md:flex-row items-center justify-between gap-4 border-t border-border pt-4">
                  <Button variant="secondary" onClick={onClose} className="text-xs uppercase font-bold tracking-wider">
                    Añadir más productos
                  </Button>
                  <div className="text-right flex items-center gap-6">
                    <div>
                      <span className="text-[10px] text-text-sub font-bold uppercase tracking-wider block">Subtotal</span>
                      <span className="font-heading font-black text-xl text-primary">Bs. {cartSubtotal.toFixed(2)}</span>
                    </div>
                    <Button onClick={handleNextToStep2} className="text-xs uppercase font-bold tracking-wider">
                      Datos de Entrega &rarr;
                    </Button>
                  </div>
                </div>
              </>
            )}
          </div>
        )}

        {/* STEP 2: CUSTOMER & DELIVERY DATA */}
        {step === 2 && (
          <div className="space-y-6">
            <div className="flex items-center gap-2">
              <FiUser className="text-primary text-sm" />
              <Typography variant="h4" className="font-heading font-black text-sm text-text-main">
                Ingresa los datos para la entrega
              </Typography>
            </div>

            <form className="space-y-5">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  label="Nombre Completo"
                  placeholder="Ej. María José Soto"
                  {...register('customer_name')}
                  error={errors.customer_name?.message}
                />
                <Input
                  label="Teléfono de Contacto"
                  placeholder="Ej. +56912345678"
                  {...register('customer_phone')}
                  error={errors.customer_phone?.message}
                />
              </div>

              {/* Delivery Type segments */}
              <div className="space-y-2">
                <Typography variant="label" className="text-[10px] uppercase tracking-widest block font-bold">
                  Tipo de Entrega
                </Typography>
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      register('delivery_type').onChange({ target: { name: 'delivery_type', value: 'Retiro en tienda' } })
                    }}
                    className={`flex-1 py-3 px-4 rounded-lg border font-bold text-xs text-center transition-all ${
                      deliveryType === 'Retiro en tienda'
                        ? 'border-primary bg-secondary/15 ring-1 ring-primary text-primary'
                        : 'border-border hover:border-stone-400 bg-surface text-text-sub'
                    }`}
                  >
                    Retiro en Tienda
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      register('delivery_type').onChange({ target: { name: 'delivery_type', value: 'Delivery' } })
                    }}
                    className={`flex-1 py-3 px-4 rounded-lg border font-bold text-xs text-center transition-all ${
                      deliveryType === 'Delivery'
                        ? 'border-primary bg-secondary/15 ring-1 ring-primary text-primary'
                        : 'border-border hover:border-stone-400 bg-surface text-text-sub'
                    }`}
                  >
                    Delivery / Despacho
                  </button>
                </div>
              </div>

              {/* Delivery Address (only if type is Delivery) */}
              {deliveryType === 'Delivery' && (
                <div className="animate-slide-down">
                  <Textarea
                    label="Dirección de Despacho"
                    placeholder="Ej. Av. Providencia 1234, Depto 402, Providencia"
                    {...register('address')}
                    error={errors.address?.message}
                  />
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  label="Fecha de Entrega Requerida"
                  type="date"
                  min={new Date().toISOString().split('T')[0]}
                  {...register('delivery_date')}
                  error={errors.delivery_date?.message}
                />
                <Input
                  label="Hora de Entrega"
                  type="time"
                  {...register('delivery_time')}
                  error={errors.delivery_time?.message}
                />
              </div>

              <Textarea
                label="Observaciones / Comentarios adicionales (Opcional)"
                placeholder="Ej. Por favor dejar en consejería si no respondo, o agregar una dedicatoria: '¡Feliz Cumpleaños!'"
                {...register('observations')}
                error={errors.observations?.message}
              />
            </form>

            <div className="flex items-center justify-between border-t border-border pt-4">
              <Button variant="secondary" onClick={() => setStep(1)} className="text-xs uppercase font-bold tracking-wider">
                &larr; Volver
              </Button>
              <Button onClick={handleNextToStep3} className="text-xs uppercase font-bold tracking-wider">
                Resumen y Confirmar &rarr;
              </Button>
            </div>
          </div>
        )}

        {/* STEP 3: ORDER SUMMARY & CONFIRMATION */}
        {step === 3 && (
          <div className="space-y-6">
            <div className="flex items-center gap-2">
              <FiInfo className="text-primary text-sm" />
              <Typography variant="h4" className="font-heading font-black text-sm text-text-main">
                Confirma los detalles de tu pedido
              </Typography>
            </div>

            {/* Summary Information cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="border border-border rounded-lg p-4 bg-stone-50/50 dark:bg-stone-900/50 space-y-3">
                <h4 className="font-heading font-black text-xs text-primary uppercase tracking-wider">Información del Cliente</h4>
                <div className="text-xs space-y-1.5 font-medium">
                  <p><strong className="text-text-sub font-bold">Nombre:</strong> {customerName}</p>
                  <p><strong className="text-text-sub font-bold">Teléfono:</strong> {customerPhone}</p>
                  <p>
                    <strong className="text-text-sub font-bold">Entrega:</strong>{' '}
                    <Badge variant={deliveryType === 'Delivery' ? 'info' : 'neutral'}>{deliveryType}</Badge>
                  </p>
                  {deliveryType === 'Delivery' && (
                    <p><strong className="text-text-sub font-bold">Dirección:</strong> {address}</p>
                  )}
                </div>
              </div>

              <div className="border border-border rounded-lg p-4 bg-stone-50/50 dark:bg-stone-900/50 space-y-3">
                <h4 className="font-heading font-black text-xs text-primary uppercase tracking-wider">Detalles de Entrega</h4>
                <div className="text-xs space-y-1.5 font-medium">
                  <p>
                    <strong className="text-text-sub font-bold">Fecha Requerida:</strong>{' '}
                    {new Date(`${deliveryDate}T12:00:00`).toLocaleDateString('es-CL', { dateStyle: 'long' })}
                  </p>
                  <p><strong className="text-text-sub font-bold">Hora Requerida:</strong> {deliveryTime} hrs</p>
                  <p><strong className="text-text-sub font-bold">Observaciones:</strong> {observations || 'Sin comentarios adicionales.'}</p>
                </div>
              </div>
            </div>

            {/* Items summary */}
            <div className="border border-border rounded-lg overflow-hidden bg-background">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-stone-50/80 dark:bg-stone-900/80 border-b border-border text-[9px] font-bold text-text-sub uppercase tracking-wider">
                    <th className="p-3">Producto / Presentación</th>
                    <th className="p-3 text-center">Cant.</th>
                    <th className="p-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border text-xs">
                  {cartItems.map((item) => {
                    const price = (item.promo_price !== undefined && item.promo_price !== null ? item.promo_price : item.base_price) +
                      item.extras.reduce((sum, e) => sum + Number(e.price), 0)
                    const itemSubtotal = price * item.quantity

                    return (
                      <tr key={item.id} className="hover:bg-stone-50/20 transition-colors">
                        <td className="p-3">
                          <span className="font-bold text-text-main">{item.product_name}</span>
                          <span className="text-[10px] text-text-sub font-semibold block italic">{item.variant_name}</span>
                          {item.extras.length > 0 && (
                            <span className="text-[9px] text-text-sub/70 block font-semibold mt-0.5">
                              + Adicionales: {item.extras.map(e => e.name).join(', ')}
                            </span>
                          )}
                        </td>
                        <td className="p-3 text-center font-bold text-text-main">{item.quantity}</td>
                        <td className="p-3 text-right font-bold text-primary">Bs. {itemSubtotal.toFixed(2)}</td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>

            {/* Error notifications */}
            {submitError && (
              <div className="text-red-500 text-xs font-semibold bg-red-50 dark:bg-red-950/20 p-3 rounded-lg border border-red-200 animate-fade-in pl-4">
                <p>{submitError}</p>
              </div>
            )}

            <div className="flex items-center justify-between border-t border-border pt-4">
              <Button variant="secondary" onClick={() => setStep(2)} className="text-xs uppercase font-bold tracking-wider" disabled={isSubmitting}>
                &larr; Volver
              </Button>
              <div className="flex items-center gap-4">
                <div className="text-right">
                  <span className="text-[9px] text-text-sub font-bold uppercase tracking-wider block">Total General</span>
                  <span className="font-heading font-black text-lg text-primary">Bs. {cartSubtotal.toFixed(2)}</span>
                </div>
                <Button 
                  onClick={handleSubmit(onSubmit)} 
                  disabled={isSubmitting}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600 font-bold uppercase tracking-wider text-xs px-6"
                >
                  {isSubmitting ? 'Procesando...' : (
                    <span className="flex items-center gap-1.5 justify-center">
                      <FiCheck className="text-sm" />
                      <span>Finalizar Pedido</span>
                    </span>
                  )}
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Modal>
  )
}
