import React from 'react'
import { ConfirmDialog } from '@/design-system'

export interface CrudDeleteDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title?: string;
  message?: string;
  confirmText?: string;
  cancelText?: string;
  isLoading?: boolean;
}

export const CrudDeleteDialog: React.FC<CrudDeleteDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title = '¿Eliminar Registro?',
  message = 'Estás a punto de eliminar permanentemente este registro. Esta acción no se puede deshacer.',
  confirmText = 'Sí, Eliminar',
  cancelText = 'Cancelar',
  isLoading = false
}) => {
  return (
    <ConfirmDialog
      isOpen={isOpen}
      onClose={onClose}
      onConfirm={onConfirm}
      title={title}
      message={message}
      confirmText={confirmText}
      cancelText={cancelText}
      isLoading={isLoading}
      variant="danger"
    />
  )
}
