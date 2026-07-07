import React, { useState, useRef, useEffect } from 'react'
import { cn } from '@/shared/utils/cn'

export interface DropdownItemDef {
  label: string;
  onClick: () => void;
  icon?: string;
  className?: string;
}

export interface DropdownProps {
  trigger: React.ReactNode;
  items: DropdownItemDef[];
  align?: 'left' | 'right';
}

export const Dropdown: React.FC<DropdownProps> = ({ 
  trigger, 
  items, 
  align = 'right' 
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleOutsideClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleOutsideClick)
    return () => document.removeEventListener('mousedown', handleOutsideClick)
  }, [])

  return (
    <div ref={containerRef} className="relative inline-block text-left">
      <div 
        onClick={() => setIsOpen(!isOpen)}
        className="cursor-pointer"
      >
        {trigger}
      </div>

      {isOpen && (
        <div className={cn(
          'absolute mt-2 w-48 rounded-lg bg-surface border border-border shadow-xl py-1.5 z-50 animate-scale-up',
          align === 'right' ? 'right-0' : 'left-0'
        )}>
          {items.map((item, index) => (
            <button
              key={index}
              type="button"
              onClick={() => {
                item.onClick()
                setIsOpen(false)
              }}
              className={cn(
                'w-full text-left px-4 py-2 text-xs text-text-sub hover:bg-stone-50 hover:text-text-main transition-colors flex items-center gap-2.5 first-of-type:rounded-t-md last-of-type:rounded-b-md font-bold',
                item.className
              )}
            >
              {item.icon && <span>{item.icon}</span>}
              <span>{item.label}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
