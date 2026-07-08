import { UseFormReturn, FieldValues } from 'react-hook-form'
import { CrudFormField, CrudFieldDef } from './CrudFormField'
import { Button } from '@/design-system'

export interface CrudFormProps<TFieldValues extends FieldValues = FieldValues> {
  fields: CrudFieldDef[];
  form: UseFormReturn<TFieldValues>;
  onSubmit: (data: TFieldValues) => void;
  onCancel: () => void;
  submitLabel?: string;
  cancelLabel?: string;
  isPending?: boolean;
  children?: React.ReactNode;
}

export function CrudForm<TFieldValues extends FieldValues = FieldValues>({
  fields,
  form,
  onSubmit,
  onCancel,
  submitLabel = 'Guardar',
  cancelLabel = 'Cancelar',
  isPending = false,
  children
}: CrudFormProps<TFieldValues>) {
  const { register, handleSubmit, formState: { errors } } = form

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {fields.map((field) => {
          const isFullWidth = field.type === 'textarea' || field.type === 'switch'
          return (
            <div 
              key={field.name}
              className={isFullWidth ? 'md:col-span-2' : 'col-span-1'}
            >
              <CrudFormField
                field={field}
                register={register}
                error={errors[field.name]?.message as string | undefined}
              />
            </div>
          )
        })}
      </div>

      {children}

      <div className="flex items-center justify-end gap-3 border-t border-stone-100 pt-5">
        <Button
          type="button"
          variant="neutral"
          onClick={onCancel}
          disabled={isPending}
        >
          {cancelLabel}
        </Button>
        <Button
          type="submit"
          variant="primary"
          isLoading={isPending}
        >
          {submitLabel}
        </Button>
      </div>
    </form>
  )
}
