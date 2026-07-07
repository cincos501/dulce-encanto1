import { toast as sonnerToast } from 'sonner'

export const toast = {
  success: (message: string) => {
    sonnerToast.success(message, {
      className: 'rounded-2xl border-emerald-100 bg-white font-sans text-stone-850 font-bold shadow-lg',
    })
  },
  error: (message: string) => {
    sonnerToast.error(message, {
      className: 'rounded-2xl border-rose-100 bg-white font-sans text-stone-850 font-bold shadow-lg',
    })
  },
  info: (message: string) => {
    sonnerToast.info(message, {
      className: 'rounded-2xl border-pink-100 bg-white font-sans text-stone-850 font-bold shadow-lg',
    })
  }
}
