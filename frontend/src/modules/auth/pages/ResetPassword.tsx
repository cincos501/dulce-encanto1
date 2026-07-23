import React, { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { useNavigate, useSearchParams, Link } from 'react-router-dom'
import { toast } from 'sonner'
import api from '@/lib/axios'
import { Input, Button, Label, Typography } from '@/design-system'
import { FiStar, FiArrowLeft, FiLock } from 'react-icons/fi'
import { handleApiError } from '@/shared/utils/formErrors'

const resetPasswordSchema = z.object({
  password: z.string().min(6, 'La contraseña debe tener al menos 6 caracteres.'),
  password_confirmation: z.string().min(6, 'La confirmación debe tener al menos 6 caracteres.')
}).refine(data => data.password === data.password_confirmation, {
  message: 'Las contraseñas no coinciden.',
  path: ['password_confirmation']
})

type ResetPasswordFormInputs = z.infer<typeof resetPasswordSchema>

export default function ResetPassword() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)

  const token = searchParams.get('token') || ''
  const email = searchParams.get('email') || ''

  const { register, handleSubmit, formState: { errors }, setError } = useForm<ResetPasswordFormInputs>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: '',
      password_confirmation: ''
    }
  })

  const onSubmit = async (data: ResetPasswordFormInputs) => {
    if (!token || !email) {
      toast.error('Enlace de restablecimiento inválido. Por favor, solicita uno nuevo.')
      return
    }

    setIsSubmitting(true)
    try {
      const payload = {
        token,
        email,
        password: data.password,
        password_confirmation: data.password_confirmation
      }
      const response = await api.post('/api/v1/auth/reset-password', payload)
      toast.success(response.data.message || 'Contraseña restablecida con éxito.')
      navigate('/login')
    } catch (error: any) {
      handleApiError(error, setError, 'No se pudo restablecer la contraseña.')
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
            <FiLock className="stroke-[1.5]" />
          </div>
          <Typography variant="h1" className="text-2xl font-black text-primary leading-tight">
            Nueva Contraseña
          </Typography>
          <Typography variant="body" className="text-text-sub text-xs">
            Ingresa tu nueva contraseña para volver a acceder al panel administrativo.
          </Typography>
        </div>

        {!token || !email ? (
          <div className="p-4 rounded-lg bg-red-50/50 border border-red-200 text-red-800 text-xs font-semibold leading-relaxed text-center space-y-4">
            <p>Faltan parámetros requeridos (token o correo) en el enlace.</p>
            <Link to="/forgot-password" className="block">
              <Button variant="primary" className="w-full py-2.5 text-xs font-bold uppercase tracking-wider">
                Solicitar nuevo enlace
              </Button>
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="space-y-1.5">
              <Label htmlFor="password" required>Nueva Contraseña</Label>
              <Input
                id="password"
                type="password"
                placeholder="Mínimo 6 caracteres"
                error={errors.password?.message}
                {...register('password')}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="password_confirmation" required>Confirmar Contraseña</Label>
              <Input
                id="password_confirmation"
                type="password"
                placeholder="Repite la contraseña"
                error={errors.password_confirmation?.message}
                {...register('password_confirmation')}
              />
            </div>

            <Button
              type="submit"
              variant="primary"
              className="w-full py-3 text-xs uppercase tracking-wider font-bold"
              isLoading={isSubmitting}
            >
              Restablecer Contraseña
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
