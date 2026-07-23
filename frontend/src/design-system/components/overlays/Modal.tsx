import React from 'react'
import { cn } from '@/shared/utils/cn'
import { FiX } from 'react-icons/fi'

export interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  maxWidthClassName?: string;
}

export const Modal: React.FC<ModalProps> = ({ 
  isOpen, 
  onClose, 
  title, 
  children, 
  maxWidthClassName = 'max-w-lg' 
}) => {
  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm animate-fade-in overflow-y-auto">
      <div 
        onClick={onClose}
        className="absolute inset-0 cursor-default"
      ></div>
      <div className={cn(
        'bg-surface w-full rounded-lg border border-border shadow-2xl p-6 sm:p-7 space-y-6 relative overflow-hidden animate-scale-up z-10 my-8',
        maxWidthClassName
      )}>
        {/* Header */}
        <div className="flex items-center justify-between border-b border-border/60 pb-4">
          {title && (
            <h2 className="text-base font-heading font-black text-primary">{title}</h2>
          )}
          <button 
            type="button"
            onClick={onClose}
            className="text-text-sub hover:text-primary text-base transition-colors p-1 flex items-center justify-center cursor-pointer"
          >
            <FiX className="w-4 h-4" />
          </button>
        </div>

        {/* Body */}
        <div className="max-h-[70vh] overflow-y-auto pr-1">
          {children}
        </div>
      </div>
    </div>
  )
}
