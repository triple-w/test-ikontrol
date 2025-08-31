<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'rfc'            => ['required','string','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/'],
            'razon_social'   => ['required','string','max:200'],
            'email'          => ['nullable','email','max:90'],
            'telefono'       => ['nullable','string','max:30'],
            'regimen_fiscal' => ['nullable','string','max:5', Rule::in(array_keys(config('catalogos.regimenes_fiscales')))],
            'codigo_postal'  => ['nullable','string','max:10'],
            'calle'          => ['nullable','string','max:100'],
            'no_ext'         => ['nullable','string','max:20'],
            'no_int'         => ['nullable','string','max:20'],
            'colonia'        => ['nullable','string','max:50'],
            'municipio'      => ['nullable','string','max:50'],
            'localidad'      => ['nullable','string','max:50'],
            'estado'         => ['nullable','string', Rule::in(config('catalogos.estados_mx'))],
            'pais'           => ['nullable','string','max:30'],
            'nombre_contacto'=> ['nullable','string','max:150'],
            'regimen_fiscal' => [
            'required','string','max:5',
            Rule::in(array_keys(config('catalogos.regimenes_fiscales'))),
        ],
        ];
    }

    public function messages(): array
    {
        return [
            'rfc.regex' => 'El RFC debe tener formato válido (12/13 caracteres: AAAA + yymmdd + homoclave).',
            'regimen_fiscal.required' => 'Selecciona un régimen fiscal.',
            'regimen_fiscal.in' => 'Selecciona un régimen fiscal válido.',
            'estado.in' => 'Selecciona un estado de la lista.',
        ];
    }

}
