import { UseFormRegister, FieldValues } from 'react-hook-form'
import { Input, Textarea, Select, Checkbox, Switch, Label, HelperText } from '@/design-system'

export interface CrudFieldOption {
  value: string | number;
  label: string;
}

export interface CrudFieldDef {
  name: string;
  label: string;
  type: 'text' | 'number' | 'password' | 'textarea' | 'select' | 'checkbox' | 'switch' | 'datetime-local';
  placeholder?: string;
  options?: CrudFieldOption[];
  required?: boolean;
  disabled?: boolean;
  step?: string;
}

export interface CrudFormFieldProps<TFieldValues extends FieldValues = FieldValues> {
  field: CrudFieldDef;
  register: UseFormRegister<TFieldValues>;
  error?: string;
}

export function CrudFormField<TFieldValues extends FieldValues = FieldValues>({ 
  field, 
  register, 
  error 
}: CrudFormFieldProps<TFieldValues>) {
  const { name, label, type, placeholder, options = [], required = false, disabled = false, step } = field

  return (
    <div className="space-y-1.5 w-full">
      {type !== 'checkbox' && type !== 'switch' && (
        <Label htmlFor={name} required={required}>
          {label}
        </Label>
      )}

      {type === 'text' && (
        <Input
          id={name}
          type="text"
          placeholder={placeholder}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'datetime-local' && (
        <Input
          id={name}
          type="datetime-local"
          placeholder={placeholder}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'password' && (
        <Input
          id={name}
          type="password"
          placeholder={placeholder}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'number' && (
        <Input
          id={name}
          type="number"
          step={step || '1'}
          placeholder={placeholder}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'textarea' && (
        <Textarea
          id={name}
          placeholder={placeholder}
          disabled={disabled}
          error={error}
          rows={3}
          {...register(name)}
        />
      )}

      {type === 'select' && (
        <Select
          id={name}
          options={options}
          placeholder={placeholder || '-- Seleccione --'}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'checkbox' && (
        <Checkbox
          id={name}
          label={label}
          disabled={disabled}
          error={error}
          {...register(name)}
        />
      )}

      {type === 'switch' && (
        <Switch
          id={name}
          label={label}
          description={placeholder}
          disabled={disabled}
          {...register(name)}
        />
      )}
    </div>
  )
}
