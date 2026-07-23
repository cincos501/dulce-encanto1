<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'delivery_type' => ['required', 'string', Rule::in(['Retiro en tienda', 'Delivery'])],
            'address' => ['required_if:delivery_type,Delivery', 'nullable', 'string', 'max:1000'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'delivery_date' => ['required', 'date_format:Y-m-d'],
            'delivery_time' => ['required', 'date_format:H:i'],
            
            // Items validation
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            
            // Item extras validation
            'items.*.extras' => ['nullable', 'array'],
            'items.*.extras.*' => [
                'required',
                'integer',
                Rule::exists('extras', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es requerido.',
            'customer_phone.required' => 'El teléfono de contacto es requerido.',
            'delivery_type.required' => 'El tipo de entrega es requerido.',
            'delivery_type.in' => 'El tipo de entrega seleccionado no es válido.',
            'address.required_if' => 'La dirección es requerida cuando el tipo de entrega es Delivery.',
            'delivery_date.required' => 'La fecha de entrega es requerida.',
            'delivery_date.date_format' => 'La fecha de entrega debe tener el formato AAAA-MM-DD.',
            'delivery_time.required' => 'La hora de entrega es requerida.',
            'delivery_time.date_format' => 'La hora de entrega debe tener el formato HH:MM.',
            'items.required' => 'El carrito no puede estar vacío.',
            'items.min' => 'El pedido debe contener al menos un producto.',
            'items.*.product_variant_id.required' => 'El producto seleccionado no es válido.',
            'items.*.product_variant_id.exists' => 'La presentación seleccionada no existe o se encuentra inactiva.',
            'items.*.quantity.required' => 'La cantidad es requerida.',
            'items.*.quantity.min' => 'La cantidad mínima por producto es 1.',
            'items.*.extras.*.exists' => 'Uno de los adicionales seleccionados no existe o se encuentra inactivo.',
        ];
    }
}
