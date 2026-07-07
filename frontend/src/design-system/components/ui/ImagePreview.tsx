import React from 'react'
import { cn } from '@/shared/utils/cn'
import { FiStar, FiTrash2 } from 'react-icons/fi'

export interface ImagePreviewProps extends React.HTMLAttributes<HTMLDivElement> {
  src: string;
  alt?: string;
  isPrimary?: boolean;
  onMakePrimary?: () => void;
  onDelete?: () => void;
  showActions?: boolean;
}

export const ImagePreview: React.FC<ImagePreviewProps> = ({
  className,
  src,
  alt = 'Preview image',
  isPrimary = false,
  onMakePrimary,
  onDelete,
  showActions = true,
  ...props
}) => {
  return (
    <div
      className={cn(
        'group bg-background rounded-2xl border border-border overflow-hidden relative aspect-square flex flex-col shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md',
        className
      )}
      {...props}
    >
      {/* Badge is_primary / Action to make primary */}
      {isPrimary ? (
        <span className="absolute top-2.5 left-2.5 bg-amber-500 text-white text-[10px] font-extrabold px-2.5 py-1 rounded-full shadow-sm z-10 select-none flex items-center gap-1">
          <FiStar className="fill-white text-xs" />
          <span>Principal</span>
        </span>
      ) : (
        showActions && onMakePrimary && (
          <button
            type="button"
            onClick={onMakePrimary}
            className="absolute top-2.5 left-2.5 bg-black/60 hover:bg-amber-500 text-white text-[10px] font-extrabold px-2.5 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-150 z-10 active:scale-95 cursor-pointer"
          >
            Hacer principal
          </button>
        )
      )}

      {/* Delete button */}
      {showActions && onDelete && (
        <button
          type="button"
          onClick={onDelete}
          className="absolute top-2.5 right-2.5 bg-surface/90 hover:bg-red-50 hover:text-red-600 text-text-main p-2 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-150 shadow-sm z-10 active:scale-95 flex items-center justify-center cursor-pointer"
          title="Eliminar foto"
        >
          <FiTrash2 className="text-xs" />
        </button>
      )}

      {/* Image display */}
      <div className="w-full flex-grow relative overflow-hidden bg-surface">
        <img
          src={src}
          alt={alt}
          className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
          onError={(e) => {
            (e.target as HTMLImageElement).src = 'https://placehold.co/400x400?text=Error+al+cargar'
          }}
        />
      </div>
    </div>
  )
}
