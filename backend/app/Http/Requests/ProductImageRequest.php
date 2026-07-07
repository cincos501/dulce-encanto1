<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
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
        return [
            'product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
            ],
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // max 2MB
            ],
            'is_primary' => ['nullable', 'boolean'],
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
            'product_variant_id.required' => 'La variante de producto es requerida.',
            'product_variant_id.integer' => 'La variante seleccionada no es válida.',
            'product_variant_id.exists' => 'La variante seleccionada no existe.',
            'image.required' => 'La imagen es requerida.',
            'image.file' => 'La carga debe ser un archivo válido.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'El formato de imagen debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no puede superar los 2MB de tamaño.',
            'is_primary.boolean' => 'El campo principal debe ser verdadero o falso.',
        ];
    }
}
