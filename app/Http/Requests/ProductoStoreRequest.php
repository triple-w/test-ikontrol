<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoStoreRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'clave'              => ['nullable','string','max:50'],
            'unidad'             => ['nullable','string','max:20'],
            'precio'             => ['required','numeric','min:0'],
            'descripcion'        => ['required','string','max:255'],
            'observaciones'      => ['nullable','string'],
            'clave_prod_serv_id' => ['nullable','integer', Rule::exists('clave_prod_serv','id')],
            'clave_unidad_id'    => ['nullable','integer', Rule::exists('clave_unidad','id')],
        ];
    }

    public function messages(): array
    {
        return [
            'precio.required'     => 'El precio es obligatorio.',
            'descripcion.required'=> 'La descripción es obligatoria.',
            'clave_prod_serv_id.exists' => 'La clave Producto/Servicio no es válida.',
            'clave_unidad_id.exists'    => 'La clave de Unidad no es válida.',
        ];
    }
}
