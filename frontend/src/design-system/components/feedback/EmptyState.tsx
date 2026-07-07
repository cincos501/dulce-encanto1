import React from 'react'
import { Button } from '../ui/Button'
import { cn } from '@/shared/utils/cn'
import { FiInbox } from 'react-icons/fi'

export interface EmptyStateProps extends React.HTMLAttributes<HTMLDivElement> {
  title: string;
  description: string;
  icon?: React.ReactNode;
  actionLabel?: string;
  onAction?: () => void;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  className,
  title,
  description,
  icon,
  actionLabel,
  onAction,
  ...props
}) => {
  return (
    <div
      className={cn('py-16 text-center space-y-3 max-w-sm mx-auto animate-fade-in', className)}
      {...props}
    >
      <div className="text-4xl text-text-sub/50 flex justify-center select-none">
        {icon || <FiInbox className="stroke-[1.5]" />}
      </div>
      <h3 className="font-heading font-black text-base text-primary">{title}</h3>
      <p className="text-text-sub text-xs leading-relaxed">{description}</p>
      {actionLabel && onAction && (
        <div className="pt-2">
          <Button
            type="button"
            variant="secondary"
            onClick={onAction}
            className="text-xs"
          >
            {actionLabel}
          </Button>
        </div>
      )}
    </div>
  )
}
