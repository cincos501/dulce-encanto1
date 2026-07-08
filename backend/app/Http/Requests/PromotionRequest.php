<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
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
        $promotion = $this->route('promotion');
        $promotionId = is_object($promotion) ? $promotion->id : $promotion;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('promotions', 'name')->ignore($promotionId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'discount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date' => ['required', 'date_format:Y-m-d H:i:s', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'product_variant_ids' => ['nullable', 'array'],
            'product_variant_ids.*' => ['integer', 'exists:product_variants,id'],
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
            'name.required' => 'El nombre de la promoción es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe una promoción con este nombre.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'discount_type.required' => 'El tipo de descuento es requerido.',
            'discount_type.in' => 'El tipo de descuento debe ser porcentaje o valor fijo.',
            'discount.required' => 'El valor del descuento es requerido.',
            'discount.numeric' => 'El descuento debe ser un valor numérico.',
            'discount.min' => 'El descuento no puede ser negativo.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato Y-m-d H:i:s.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato Y-m-d H:i:s.',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
