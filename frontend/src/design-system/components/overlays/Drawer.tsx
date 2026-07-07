import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface DrawerProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  side?: 'left' | 'right';
}

export const Drawer: React.FC<DrawerProps> = ({ 
  isOpen, 
  onClose, 
  title, 
  children, 
  side = 'right' 
}) => {
  return (
    <div className={cn(
      'fixed inset-0 z-50 transition-all duration-300',
      isOpen ? 'visible pointer-events-auto' : 'invisible pointer-events-none'
    )}>
      {/* Backdrop */}
      <div 
        onClick={onClose}
        className={cn(
          'absolute inset-0 bg-stone-905/60 backdrop-blur-xs transition-opacity duration-300',
          isOpen ? 'opacity-100' : 'opacity-0'
        )}
      ></div>

      {/* Sheet */}
      <div className={cn(
        'absolute top-0 bottom-0 bg-white w-full max-w-md shadow-2xl flex flex-col justify-between py-6 px-6 z-10 transition-transform duration-300',
        side === 'left' 
          ? (isOpen ? 'translate-x-0 left-0' : '-translate-x-full left-0')
          : (isOpen ? 'translate-x-0 right-0' : 'translate-x-full right-0')
      )}>
        <div className="space-y-6 flex-grow flex flex-col overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between border-b border-stone-100 pb-4">
            {title && (
              <h3 className="font-extrabold text-stone-900 text-lg">{title}</h3>
            )}
            <button 
              type="button"
              onClick={onClose}
              className="text-stone-400 hover:text-stone-700 text-lg transition-colors p-1"
            >
              ✕
            </button>
          </div>

          {/* Body */}
          <div className="flex-grow overflow-y-auto pr-1">
            {children}
          </div>
        </div>
      </div>
    </div>
  )
}
