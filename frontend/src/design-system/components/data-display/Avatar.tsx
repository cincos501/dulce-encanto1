import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface AvatarProps extends React.HTMLAttributes<HTMLDivElement> {
  name: string;
  size?: 'sm' | 'md' | 'lg';
}

export const Avatar: React.FC<AvatarProps> = ({ className, name, size = 'md', ...props }) => {
  const initials = name
    ?.split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase() || '?'

  return (
    <div
      className={cn(
        'rounded-full bg-rose-50 text-rose-500 font-extrabold border border-rose-150 flex items-center justify-center shadow-sm select-none',
        size === 'sm' && 'w-8 h-8 text-xs',
        size === 'md' && 'w-10 h-10 text-sm',
        size === 'lg' && 'w-12 h-12 text-base',
        className
      )}
      {...props}
    >
      {initials}
    </div>
  )
}
