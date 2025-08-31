<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\RfcUsuario;

class FolioStoreRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $rfc = session('rfc_seleccionado');
        $ruId = RfcUsuario::where('user_id', auth()->id())->where('rfc', $rfc)->value('id');

        return [
            'tipo'  => ['required', Rule::in(['ingreso','egreso','traslado','pagos'])],
            'serie' => ['required','string','max:10','regex:/^[A-Z0-9\-]+$/'],
            'folio' => ['required','integer','min:0'],
            // único por RFC + tipo + serie
            'serie' => [
                'required','string','max:10','regex:/^[A-Z0-9\-]+$/',
                Rule::unique('folios')->where(fn($q) =>
                    $q->where('rfc_usuario_id', $ruId)->where('tipo', $this->input('tipo'))
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona el tipo de documento.',
            'tipo.in'       => 'Tipo inválido.',
            'serie.required'=> 'La serie es obligatoria.',
            'serie.regex'   => 'Usa solo letras, números o guión.',
            'serie.unique'  => 'Ya existe una serie con ese tipo en este RFC.',
            'folio.required'=> 'Indica el folio actual (número).',
            'folio.integer' => 'El folio debe ser un número entero.',
            'folio.min'     => 'El folio debe ser cero o mayor.',
        ];
    }
}
