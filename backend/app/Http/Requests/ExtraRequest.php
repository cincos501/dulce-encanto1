<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtraRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extra = $this->route('extra');
        $extraId = is_object($extra) ? $extra->id : $extra;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('extras', 'name')->ignore($extraId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del extra es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe un extra con este nombre.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'price.required' => 'El precio adicional es requerido.',
            'price.numeric' => 'El precio debe ser un valor numérico.',
            'price.min' => 'El precio adicional no puede ser menor a 0.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
