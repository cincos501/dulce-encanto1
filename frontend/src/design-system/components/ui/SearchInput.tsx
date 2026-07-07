import React from 'react'
import { cn } from '@/shared/utils/cn'
import { FiSearch, FiX } from 'react-icons/fi'

export interface SearchInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  onClear?: () => void;
}

export const SearchInput = React.forwardRef<HTMLInputElement, SearchInputProps>(
  ({ className, onClear, value, ...props }, ref) => {
    return (
      <div className="relative w-full">
        <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center text-text-sub/60 pointer-events-none">
          <FiSearch className="text-sm shrink-0" />
        </span>
        <input
          ref={ref}
          type="text"
          value={value}
          className={cn(
            'w-full pl-10 pr-10 py-2.5 rounded-xl border border-border focus:border-primary focus:ring-2 focus:ring-stone-200/20 outline-none text-xs text-text-main transition-all duration-200 bg-surface disabled:bg-stone-100 dark:disabled:bg-stone-800 disabled:text-text-sub/50',
            className
          )}
          {...props}
        />
        {value && onClear && (
          <button
            type="button"
            onClick={onClear}
            className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-text-sub/60 hover:text-primary transition-colors cursor-pointer"
          >
            <FiX className="text-sm shrink-0" />
          </button>
        )}
      </div>
    )
  }
)

SearchInput.displayName = 'SearchInput'
