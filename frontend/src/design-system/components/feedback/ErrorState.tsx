import React from 'react'
import { Button } from '../ui/Button'
import { cn } from '@/shared/utils/cn'

export interface ErrorStateProps extends React.HTMLAttributes<HTMLDivElement> {
  title?: string;
  description?: string;
  onRetry?: () => void;
}

export const ErrorState: React.FC<ErrorStateProps> = ({
  className,
  title = 'Ha ocurrido un problema',
  description = 'No pudimos conectar con el servidor para obtener los datos.',
  onRetry,
  ...props
}) => {
  return (
    <div
      className={cn('py-16 text-center space-y-3 max-w-sm mx-auto animate-fade-in', className)}
      {...props}
    >
      <span className="text-4xl block select-none">⚠️</span>
      <h3 className="font-heading font-black text-base text-primary">{title}</h3>
      <p className="text-text-sub text-xs leading-relaxed">{description}</p>
      {onRetry && (
        <div className="pt-2">
          <Button
            type="button"
            variant="secondary"
            onClick={onRetry}
            className="text-xs"
          >
            Reintentar
          </Button>
        </div>
      )}
    </div>
  )
}
