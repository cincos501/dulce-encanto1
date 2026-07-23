import { toast as sonnerToast } from 'sonner'

export const toast = {
  success: (message: string) => {
    sonnerToast.success(message, {
      className: 'rounded-2xl border border-emerald-500/20 dark:border-emerald-500/30 bg-surface font-sans text-text-main font-bold shadow-lg',
    })
  },
  error: (message: string) => {
    sonnerToast.error(message, {
      className: 'rounded-2xl border border-rose-500/20 dark:border-rose-500/30 bg-surface font-sans text-text-main font-bold shadow-lg',
    })
  },
  info: (message: string) => {
    sonnerToast.info(message, {
      className: 'rounded-2xl border border-border bg-surface font-sans text-text-main font-bold shadow-lg',
    })
  }
}
