<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\FolioStoreRequest;
use App\Http\Requests\FolioUpdateRequest;
use App\Models\Folio;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;

class FoliosController extends Controller
{
    public function index(Request $r)
    {
        $items = Folio::forActiveRfc()
            ->when($r->filled('tipo'), fn($q) => $q->where('tipo', $r->tipo))
            ->when($r->filled('buscar'), function($q) use ($r){
                $t = '%'.strtoupper($r->buscar).'%';
                $q->where('serie','like',$t);
            })
            ->orderBy('tipo')->orderBy('serie')
            ->paginate(20)->withQueryString();

        return view('catalogos.folios.index', compact('items'));
    }

    public function create()
    {
        return view('catalogos.folios.create');
    }

    public function store(FolioStoreRequest $req)
    {
        $rfc = session('rfc_seleccionado'); abort_unless($rfc, 422, 'RFC activo requerido.');
        $ru  = RfcUsuario::where('user_id', auth()->id())->where('rfc', $rfc)->firstOrFail();

        $data = $req->validated();
        $data['users_id']       = auth()->id();
        $data['serie']          = strtoupper($data['serie']);
        $data['rfc_usuario_id'] = $ru->id;

        Folio::create($data);

        return redirect()->route('folios.index')->with('ok','Serie de folios creada.');
    }

    public function edit(Folio $folio)
    {
        abort_unless(optional($folio->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        return view('catalogos.folios.edit', compact('folio'));
    }

    public function update(FolioUpdateRequest $req, Folio $folio)
    {
        abort_unless(optional($folio->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);

        $data = $req->validated();
        $data['serie'] = strtoupper($data['serie']);

        $folio->update($data);

        return redirect()->route('folios.index')->with('ok','Serie de folios actualizada.');
    }

    public function destroy(Folio $folio)
    {
        abort_unless(optional($folio->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        $folio->delete();

        return redirect()->route('folios.index')->with('ok','Serie de folios eliminada.');
    }
}
