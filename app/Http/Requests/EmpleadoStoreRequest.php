<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class EmpleadoStoreRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
{
    // Tomamos claves válidas desde config; si está vacío, no aplicamos Rule::in
    $tipoContratoKeys   = array_keys(config('sat_nomina.tipo_contrato', []));
    $tipoJornadaKeys    = array_keys(config('sat_nomina.tipo_jornada', []));
    $tipoRegimenKeys    = array_keys(config('sat_nomina.tipo_regimen', []));
    $periodicidadKeys   = array_keys(config('sat_nomina.periodicidad_pago', []));
    $riesgoPuestoKeys   = array_keys(config('sat_nomina.riesgo_puesto', []));
    $bancoKeys          = array_keys(config('sat_nomina.banco', []));

    return [
        'nombre'              => ['required','string','max:200'],
        'rfc'                 => ['nullable','string','max:13','regex:/^([A-ZÑ&]{3,4})(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z\d]{2}[A\d]$/i'],
        'curp'                => ['nullable','string','max:18','regex:/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/i'],
        'num_seguro_social'   => ['nullable','regex:/^\d{11}$/'],

        'calle'               => ['nullable','string','max:100'],
        'localidad'           => ['nullable','string','max:50'],
        'no_exterior'         => ['nullable','string','max:20'],
        'no_interior'         => ['nullable','string','max:20'],
        'referencia'          => ['nullable','string','max:100'],
        'colonia'             => ['nullable','string','max:50'],
        'estado'              => ['nullable','string','max:50'],
        'municipio'           => ['nullable','string','max:50'],
        'pais'                => ['nullable','string','max:30'],
        'codigo_postal'       => ['nullable','string','max:10'],

        'telefono'            => ['nullable','string','max:30'],
        'email'               => ['nullable','email','max:100'],

        'registro_patronal'   => ['nullable','string','max:20'],

        // Campos con catálogos:
        'tipo_contrato'       => array_values(array_filter([
            'nullable','string','max:3',
            $tipoContratoKeys ? Rule::in($tipoContratoKeys) : null,
        ])),
        'numero_empleado'     => ['nullable','string','max:20'],
        'riesgo_puesto'       => array_values(array_filter([
            'nullable','string','max:1',
            $riesgoPuestoKeys ? Rule::in($riesgoPuestoKeys) : null,
        ])),
        'tipo_jornada'        => array_values(array_filter([
            'nullable','string','max:2',
            $tipoJornadaKeys ? Rule::in($tipoJornadaKeys) : null,
        ])),
        'puesto'              => ['nullable','string','max:100'],
        'fecha_inicio_laboral'=> ['nullable','date'],
        'tipo_regimen'        => array_values(array_filter([
            'nullable','string','max:2',
            $tipoRegimenKeys ? Rule::in($tipoRegimenKeys) : null,
        ])),
        'salario'             => ['nullable','numeric','min:0'],
        'periodicidad_pago'   => array_values(array_filter([
            'nullable','string','max:2',
            $periodicidadKeys ? Rule::in($periodicidadKeys) : null,
        ])),
        'salario_diario_integrado' => ['nullable','numeric','min:0'],
        'clabe'               => ['nullable','regex:/^\d{18}$/'],
        'banco'               => array_values(array_filter([
            'nullable','string','max:3',
            $bancoKeys ? Rule::in($bancoKeys) : null,
        ])),
    ];
}


    public function messages(): array
    {
        return [
            'rfc.regex'   => 'El RFC no tiene un formato válido.',
            'curp.regex'  => 'La CURP no tiene un formato válido.',
            'num_seguro_social.regex' => 'El NSS debe tener 11 dígitos.',
            'clabe.regex' => 'La CLABE debe tener 18 dígitos.',
        ];
    }
}
