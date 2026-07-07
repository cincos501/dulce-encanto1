import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface StatCardProps extends React.HTMLAttributes<HTMLDivElement> {
  title: string;
  value: string | number;
  change?: string;
  changeType?: 'increase' | 'decrease' | 'neutral';
  icon?: React.ReactNode;
}

export const StatCard: React.FC<StatCardProps> = ({ 
  className, 
  title, 
  value, 
  change, 
  changeType = 'neutral', 
  icon, 
  ...props 
}) => {
  return (
    <div
      className={cn(
        'bg-surface rounded-lg border border-border p-5 shadow-sm flex items-center justify-between gap-4',
        className
      )}
      {...props}
    >
      <div className="space-y-1">
        <span className="text-text-sub block text-[10px] uppercase font-bold tracking-widest">{title}</span>
        <span className="text-2xl font-sans font-black text-primary block">{value}</span>
        {change && (
          <div className="flex items-center gap-1.5 text-[10px] font-bold mt-1">
            <span className={cn(
              changeType === 'increase' && 'text-emerald-700',
              changeType === 'decrease' && 'text-red-650',
              changeType === 'neutral' && 'text-text-sub'
            )}>
              {changeType === 'increase' && '↑ '}
              {changeType === 'decrease' && '↓ '}
              {change}
            </span>
          </div>
        )}
      </div>
      {icon && (
        <div className="w-10 h-10 bg-secondary/15 rounded-lg border border-secondary/30 flex items-center justify-center text-lg select-none">
          {icon}
        </div>
      )}
    </div>
  )
}
