<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $supplierId = is_object($supplier) ? $supplier->id : $supplier;

        return [
            'business_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'business_name')->ignore($supplierId),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'La razón social / nombre comercial es requerida.',
            'business_name.max' => 'La razón social no puede superar los 255 caracteres.',
            'business_name.unique' => 'Ya existe un proveedor registrado con esa razón social.',
            'phone.required' => 'El teléfono de contacto es requerido.',
            'phone.max' => 'El teléfono no puede superar los 50 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección de email válida.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'address.max' => 'La dirección no puede superar los 1000 caracteres.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
