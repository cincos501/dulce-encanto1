<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $productVariantId = $this->route('product_variant');
        if ($productVariantId) {
            $this->merge([
                'product_variant_id' => is_numeric($productVariantId) ? (int) $productVariantId : $productVariantId
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where('is_active', true),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.supply_id' => [
                'required',
                'integer',
                Rule::exists('supplies', 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.observation' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'La presentación del producto es requerida.',
            'product_variant_id.exists' => 'La presentación seleccionada no existe o se encuentra inactiva.',
            'items.required' => 'La lista de insumos es requerida.',
            'items.min' => 'La receta debe contener al menos un insumo.',
            'items.*.supply_id.required' => 'El insumo es requerido.',
            'items.*.supply_id.exists' => 'El insumo seleccionado no existe o se encuentra inactivo.',
            'items.*.supply_id.distinct' => 'No se puede duplicar el mismo insumo en la receta.',
            'items.*.quantity.required' => 'La cantidad es requerida.',
            'items.*.quantity.numeric' => 'La cantidad debe ser numérica.',
            'items.*.quantity.gt' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit.required' => 'La unidad de medida es requerida.',
            'items.*.unit.max' => 'La unidad de medida no puede superar los 50 caracteres.',
            'items.*.observation.max' => 'La observación no puede superar los 255 caracteres.',
        ];
    }
}
