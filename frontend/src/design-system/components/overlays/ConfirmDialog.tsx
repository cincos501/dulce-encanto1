import { Modal } from './Modal'
import { Button } from '../ui/Button'
import { cn } from '@/shared/utils/cn'
import { FiTrash2, FiAlertTriangle, FiBell } from 'react-icons/fi'

export interface ConfirmDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  isLoading?: boolean;
  variant?: 'danger' | 'warning' | 'info';
}

export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmText = 'Confirmar',
  cancelText = 'Cancelar',
  isLoading,
  variant = 'warning'
}) => {
  return (
    <Modal 
      isOpen={isOpen} 
      onClose={onClose} 
      maxWidthClassName="max-w-md"
    >
      <div className="text-center space-y-5">
        {/* State Icon */}
        <div className={cn(
          'w-12 h-12 rounded-lg flex items-center justify-center border mx-auto text-lg',
          variant === 'danger' && 'bg-red-50 border-red-200 text-red-700',
          variant === 'warning' && 'bg-amber-50 border-amber-200 text-amber-800',
          variant === 'info' && 'bg-secondary/15 border-secondary/30 text-primary'
        )}>
          {variant === 'danger' && <FiTrash2 />}
          {variant === 'warning' && <FiAlertTriangle />}
          {variant === 'info' && <FiBell />}
        </div>

        {/* Text */}
        <div className="space-y-1">
          <h3 className="font-heading font-black text-base text-primary">{title}</h3>
          <p className="text-text-sub text-xs leading-relaxed">{message}</p>
        </div>

        {/* Buttons */}
        <div className="flex items-center justify-center gap-3 pt-2">
          <Button
            type="button"
            variant="neutral"
            onClick={onClose}
            className="w-1/2"
            disabled={isLoading}
          >
            {cancelText}
          </Button>
          <Button
            type="button"
            variant={variant === 'danger' ? 'destructive' : 'primary'}
            onClick={onConfirm}
            className="w-1/2"
            isLoading={isLoading}
          >
            {confirmText}
          </Button>
        </div>
      </div>
    </Modal>
  )
}
