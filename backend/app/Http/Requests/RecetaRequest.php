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
            'producto_id.exists' => 'El producto seleccionado debe ser de tipo "elaborado".',
            'insumos.*.insumo_id.exists' => 'Uno o más insumos seleccionados no son válidos o no son de tipo "insumo".',
            'insumos.required' => 'La receta debe tener al menos un insumo.',
        ];
    }
}
