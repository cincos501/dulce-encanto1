import React, { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { Link } from 'react-router-dom'
import { toast } from 'sonner'
import api from '@/lib/axios'
import { Input, Button, Label, Typography } from '@/design-system'
import { FiStar, FiArrowLeft, FiMail } from 'react-icons/fi'
import { handleApiError } from '@/shared/utils/formErrors'

const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'El correo electrónico es requerido.').email('El correo electrónico no es válido.')
})

type ForgotPasswordFormInputs = z.infer<typeof forgotPasswordSchema>

export default function ForgotPassword() {
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)
  const [isSuccess, setIsSuccess] = useState<boolean>(false)

  const { register, handleSubmit, formState: { errors }, setError } = useForm<ForgotPasswordFormInputs>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: {
      email: ''
    }
  })

  const onSubmit = async (data: ForgotPasswordFormInputs) => {
    setIsSubmitting(true)
    try {
      const response = await api.post('/api/v1/auth/forgot-password', data)
      toast.success(response.data.message || 'Enlace de restablecimiento enviado.')
      setIsSuccess(true)
    } catch (error: any) {
      handleApiError(error, setError, 'No se pudo enviar el enlace de recuperación.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4 relative font-sans">
      {/* Decorative subtle background details */}
      <div className="absolute top-10 left-10 w-72 h-72 bg-secondary/5 rounded-full filter blur-3xl opacity-50 pointer-events-none select-none"></div>
      <div className="absolute bottom-10 right-10 w-72 h-72 bg-stone-200/20 rounded-full filter blur-3xl opacity-50 pointer-events-none select-none"></div>

      <div className="w-full max-w-md bg-surface rounded-lg border border-border shadow-xl p-8 relative overflow-hidden animate-scale-up">
        {/* Header logo / details */}
        <div className="text-center space-y-3 mb-8">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-secondary/15 border border-secondary/30 text-xl text-primary animate-pulse select-none">
            <FiMail className="stroke-[1.5]" />
          </div>
          <Typography variant="h1" className="text-2xl font-black text-primary leading-tight">
            Restablecer Contraseña
          </Typography>
          <Typography variant="body" className="text-text-sub text-xs">
            {isSuccess 
              ? 'Revisa tu bandeja de entrada o los registros del sistema.'
              : 'Ingresa tu correo registrado para recibir las instrucciones.'
            }
          </Typography>
        </div>

        {isSuccess ? (
          <div className="space-y-6 text-center animate-fade-in">
            <div className="p-4 rounded-lg bg-green-50/50 border border-green-200 text-green-800 text-xs font-semibold leading-relaxed">
              Hemos enviado un enlace de recuperación a tu correo electrónico. Por favor, revisa tu buzón. En desarrollo, puedes consultar los registros en <code className="bg-green-100/50 px-1 py-0.5 rounded font-mono text-[10px]">laravel.log</code>.
            </div>
            <Link to="/login" className="block">
              <Button variant="primary" className="w-full py-3 text-xs uppercase font-bold tracking-wider">
                Volver al Login
              </Button>
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="space-y-1.5">
              <Label htmlFor="email" required>Correo Electrónico</Label>
              <Input
                id="email"
                type="email"
                placeholder="ejemplo@dulceencanto.com"
                error={errors.email?.message}
                {...register('email')}
              />
            </div>

            <Button
              type="submit"
              variant="primary"
              className="w-full py-3 text-xs uppercase tracking-wider font-bold"
              isLoading={isSubmitting}
            >
              Enviar Enlace de Recuperación
            </Button>
          </form>
        )}

        <div className="mt-8 text-center text-xs text-text-sub/70 border-t border-border/60 pt-6">
          <Link to="/login" className="hover:text-primary transition-colors font-bold inline-flex items-center gap-1.5">
            <FiArrowLeft className="text-xs" />
            <span>Volver a iniciar sesión</span>
          </Link>
        </div>
      </div>
    </div>
  )
}
