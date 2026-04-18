<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where('tipo', 'elaborado'),
            ],
            'nombre' => 'required|string|max:150',
            'rendimiento' => 'required|numeric|min:0.001',
            'instrucciones' => 'nullable|string',
            'insumos' => 'required|array|min:1',
            'insumos.*.insumo_id' => [
                'required',
                Rule::exists('productos', 'id')->where('tipo', 'insumo'),
            ],
            'insumos.*.cantidad' => 'required|numeric|min:0.0001',
            'insumos.*.unidad_medida' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado debe ser de tipo "elaborado".',

            'nombre.required' => 'El nombre es obligatorio.',
            'rendimiento.required' => 'El rendimiento es obligatorio.',
            'rendimiento.numeric' => 'El rendimiento debe ser numérico.',
            'rendimiento.min' => 'El rendimiento debe ser mayor a 0.',

            'insumos.required' => 'La receta debe tener al menos un insumo.',
            'insumos.array' => 'El formato de insumos es inválido.',
            'insumos.min' => 'Debe agregar al menos un insumo.',

            'insumos.*.insumo_id.required' => 'El insumo es obligatorio.',
            'insumos.*.insumo_id.exists' => 'Uno o más insumos no son válidos.',

            'insumos.*.cantidad.required' => 'La cantidad es obligatoria.',
            'insumos.*.cantidad.numeric' => 'La cantidad debe ser numérica.',
            'insumos.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',

            'insumos.*.unidad_medida.required' => 'La unidad de medida es obligatoria.',
        ];
    }
}
