<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
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
        $variant = $this->route('product_variant');
        $variantId = is_object($variant) ? $variant->id : $variant;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                // No duplicate variants with the same name under the same product
                Rule::unique('product_variants', 'name')
                    ->where('product_id', (int) $this->product_id)
                    ->ignore($variantId),
            ],
            'price' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'serves_people' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'is_active' => ['nullable', 'boolean'],
            'extras' => [
                'nullable',
                'array',
            ],
            'extras.*.extra_id' => [
                'required',
                'integer',
                Rule::exists('extras', 'id')->where('is_active', true),
            ],
            'extras.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],
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
            'product_id.required' => 'El producto es requerido.',
            'product_id.integer' => 'El producto seleccionado no es válido.',
            'product_id.exists' => 'El producto seleccionado no existe o se encuentra inactivo.',
            'name.required' => 'La presentación de la variante es requerida.',
            'name.string' => 'La presentación debe ser una cadena de texto.',
            'name.max' => 'La presentación no puede superar los 255 caracteres.',
            'name.unique' => 'Este producto ya tiene una presentación registrada con este mismo nombre.',
            'price.required' => 'El precio de la variante es requerido.',
            'price.numeric' => 'El precio debe ser un valor numérico.',
            'price.gt' => 'El precio debe ser mayor a cero.',
            'serves_people.integer' => 'La cantidad de personas debe ser un número entero.',
            'serves_people.min' => 'La cantidad de personas debe ser al menos 1.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
            'extras.*.extra_id.required' => 'El identificador del adicional es requerido.',
            'extras.*.extra_id.exists' => 'Uno o más adicionales seleccionados no existen o se encuentran inactivos.',
            'extras.*.price.required' => 'El precio del adicional es requerido.',
            'extras.*.price.numeric' => 'El precio del adicional debe ser numérico.',
            'extras.*.price.min' => 'El precio del adicional no puede ser negativo.',
        ];
    }
}
