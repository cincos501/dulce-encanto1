import React from 'react'
import { cn } from '@/shared/utils/cn'

export interface TabOption {
  id: string;
  label: string;
  icon?: string;
}

export interface TabsProps extends React.HTMLAttributes<HTMLDivElement> {
  tabs: TabOption[];
  activeTab: string;
  onTabChange: (id: string) => void;
}

export const Tabs: React.FC<TabsProps> = ({ className, tabs, activeTab, onTabChange, ...props }) => {
  return (
    <div
      className={cn('flex border-b border-border gap-1', className)}
      {...props}
    >
      {tabs.map((tab) => {
        const isActive = activeTab === tab.id
        return (
          <button
            key={tab.id}
            type="button"
            onClick={() => onTabChange(tab.id)}
            className={cn(
              'px-4 py-2.5 text-xs font-bold border-b-2 transition-all duration-200 -mb-[1px] flex items-center gap-2 select-none active:scale-[0.98]',
              isActive
                ? 'border-primary text-primary font-black'
                : 'border-transparent text-text-sub hover:text-primary hover:border-stone-300'
            )}
          >
            {tab.icon && <span>{tab.icon}</span>}
            <span>{tab.label}</span>
          </button>
        )
      })}
    </div>
  )
}
