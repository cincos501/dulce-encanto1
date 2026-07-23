import { UseFormSetError, FieldValues, Path } from 'react-hook-form'
import { toast } from '@/design-system'

/**
 * Handle API errors, specifically mapping Laravel validation errors (422) 
 * directly to the corresponding React Hook Form fields.
 */
export function handleApiError<TFieldValues extends FieldValues>(
  error: any,
  setError: UseFormSetError<TFieldValues>,
  defaultMessage: string
): void {
  if (error.response?.status === 422) {
    const apiErrors = error.response.data?.errors
    if (apiErrors) {
      Object.keys(apiErrors).forEach((field) => {
        setError(field as Path<TFieldValues>, {
          type: 'server',
          message: apiErrors[field][0]
        })
      })
      return
    }
  }
  
  // For other errors, display a toast notification
  const message = error.response?.data?.message || defaultMessage
  toast.error(message)
}
