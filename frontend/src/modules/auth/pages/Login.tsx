import React, { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import { useNavigate, Link } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '@/app/providers/AuthContext'
import { Input, Button, Label, Typography } from '@/design-system'
import { FiStar, FiArrowLeft } from 'react-icons/fi'

const loginSchema = z.object({
  email: z.string().min(1, 'El correo electrónico es requerido.').email('El correo electrónico no es válido.'),
  password: z.string().min(6, 'La contraseña debe tener al menos 6 caracteres.')
})

type LoginFormInputs = z.infer<typeof loginSchema>

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false)

  const { register, handleSubmit, formState: { errors } } = useForm<LoginFormInputs>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: ''
    }
  })

  const onSubmit = async (data: LoginFormInputs) => {
    setIsSubmitting(true)
    try {
      const user = await login(data.email, data.password)
      toast.success(`¡Bienvenido de vuelta, ${user.full_name}!`)
      navigate('/dashboard')
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || 'Las credenciales proporcionadas son incorrectas.'
      toast.error(errorMessage)
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
            <FiStar className="stroke-[1.5]" />
          </div>
          <Typography variant="h1" className="text-2xl font-black text-primary leading-tight">
            Dulce Encanto
          </Typography>
          <Typography variant="body" className="text-text-sub text-xs">
            Ingresa tus credenciales para acceder al sistema
          </Typography>
        </div>

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

          <div className="space-y-1.5">
            <Label htmlFor="password" required>Contraseña</Label>
            <Input
              id="password"
              type="password"
              placeholder="••••••••"
              error={errors.password?.message}
              {...register('password')}
            />
          </div>

          <Button
            type="submit"
            variant="primary"
            className="w-full py-3 text-xs uppercase tracking-wider font-bold"
            isLoading={isSubmitting}
          >
            Ingresar al Panel
          </Button>
        </form>

        <div className="mt-8 text-center text-xs text-text-sub/70 border-t border-border/60 pt-6">
          <Link to="/" className="hover:text-primary transition-colors font-bold inline-flex items-center gap-1.5">
            <FiArrowLeft className="text-xs" />
            <span>Volver a la página principal</span>
          </Link>
        </div>
      </div>
    </div>
  )
}
