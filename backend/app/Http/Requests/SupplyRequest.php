<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplyRequest extends FormRequest
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
        if ($this->is('api/*/supplies/purchase') || $this->routeIs('*purchase')) {
            return [
                'supplier_id' => [
                    'required',
                    'integer',
                    Rule::exists('suppliers', 'id')->where('is_active', true),
                ],
                'items' => ['required', 'array', 'min:1'],
                'items.*.supply_id' => [
                    'required',
                    'integer',
                    Rule::exists('supplies', 'id')->where('is_active', true),
                    'distinct',
                ],
                'items.*.quantity' => ['required', 'numeric', 'gt:0'],
                'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            ];
        }

        $supply = $this->route('supply');
        $supplyId = is_object($supply) ? $supply->id : $supply;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('supplies', 'name')->ignore($supplyId),
            ],
            'unit' => [
                'required',
                'string',
                Rule::in(['kg', 'g', 'L', 'ml', 'u', 'Caja', 'Bolsa', 'Paquete']),
            ],
            'stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'average_cost' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'suppliers' => ['nullable', 'array'],
            'suppliers.*.supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'suppliers.*.purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del insumo es requerido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe un insumo registrado con este nombre.',
            'unit.required' => 'La unidad de medida es requerida.',
            'unit.in' => 'La unidad de medida seleccionada no es válida.',
            'stock.required' => 'El stock actual es requerido.',
            'stock.numeric' => 'El stock debe ser un valor numérico.',
            'stock.min' => 'El stock no puede ser negativo.',
            'minimum_stock.required' => 'El stock mínimo es requerido.',
            'minimum_stock.numeric' => 'El stock mínimo debe ser un valor numérico.',
            'minimum_stock.min' => 'El stock mínimo no puede ser negativo.',
            'average_cost.required' => 'El costo promedio es requerido.',
            'average_cost.numeric' => 'El costo debe ser un valor numérico.',
            'average_cost.min' => 'El costo no puede ser negativo.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
            'suppliers.*.supplier_id.exists' => 'El proveedor seleccionado no existe o se encuentra inactivo.',
            'suppliers.*.purchase_price.required' => 'El precio de compra es requerido.',
            'suppliers.*.purchase_price.min' => 'El precio de compra no puede ser negativo.',
            
            // Purchase messages
            'supplier_id.required' => 'El proveedor es requerido.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe o se encuentra inactivo.',
            'items.required' => 'Los artículos de compra son requeridos.',
            'items.min' => 'La compra debe contener al menos un insumo.',
            'items.*.supply_id.required' => 'El insumo es requerido.',
            'items.*.supply_id.exists' => 'El insumo seleccionado no existe o se encuentra inactivo.',
            'items.*.supply_id.distinct' => 'No se puede duplicar el mismo insumo en la compra.',
            'items.*.quantity.required' => 'La cantidad comprada es requerida.',
            'items.*.quantity.numeric' => 'La cantidad debe ser numérica.',
            'items.*.quantity.gt' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
