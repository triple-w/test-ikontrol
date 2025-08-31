<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\RfcUsuario;

class FolioUpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $folio = $this->route('folio'); // model binding
        $rfc = session('rfc_seleccionado');
        $ruId = \App\Models\RfcUsuario::where('user_id', auth()->id())->where('rfc', $rfc)->value('id');

        return [
            'tipo'  => ['required', Rule::in(['ingreso','egreso','traslado','pagos'])],
            'serie' => [
                'required','string','max:10','regex:/^[A-Z0-9\-]+$/',
                Rule::unique('folios')->ignore($folio->id)->where(fn($q) =>
                    $q->where('rfc_usuario_id', $ruId)->where('tipo', $this->input('tipo'))
                ),
            ],
            'folio' => ['required','integer','min:0'],
        ];
    }
}
