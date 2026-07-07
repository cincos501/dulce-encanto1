import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/shared/utils/cn'
import { FiCheckCircle, FiAlertOctagon, FiAlertTriangle, FiInfo } from 'react-icons/fi'

const alertVariants = cva(
  'p-4 rounded-lg border text-xs leading-relaxed flex items-start gap-3 w-full',
  {
    variants: {
      variant: {
        success: 'bg-emerald-50 border-emerald-150 text-emerald-700',
        danger: 'bg-red-50 border-red-150 text-red-700',
        warning: 'bg-amber-50 border-amber-150 text-amber-800',
        info: 'bg-secondary/15 border-secondary/35 text-primary'
      }
    },
    defaultVariants: {
      variant: 'info'
    }
  }
)

export interface AlertProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof alertVariants> {}

export const Alert: React.FC<AlertProps> = ({ className, variant, children, ...props }) => {
  return (
    <div
      className={cn(alertVariants({ variant, className }))}
      {...props}
    >
      <span className="text-sm leading-none select-none shrink-0 flex items-center pt-0.5">
        {variant === 'success' && <FiCheckCircle />}
        {variant === 'danger' && <FiAlertOctagon />}
        {variant === 'warning' && <FiAlertTriangle />}
        {variant === 'info' && <FiInfo />}
      </span>
      <div className="font-sans font-medium">{children}</div>
    </div>
  )
}
