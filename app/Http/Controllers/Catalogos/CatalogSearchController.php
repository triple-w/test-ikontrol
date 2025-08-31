<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogSearchController extends Controller
{
    public function prodServ(Request $r)
    {
        // Si viene id (para precargar etiqueta en edición)
        if ($r->filled('id')) {
            $row = DB::table('clave_prod_serv')->select(['id','clave','descripcion'])->where('id', $r->id)->first();
            return response()->json($row ? [$row] : []);
        }

        $term = trim($r->get('term', ''));
        $q = DB::table('clave_prod_serv')->select(['id','clave','descripcion'])->limit(20);

        if ($term !== '') {
            $like = '%'.$term.'%';
            $q->where(function($qq) use ($like){
                $qq->where('clave','like',$like)->orWhere('descripcion','like',$like);
            });
        }

        return response()->json($q->get());
    }

    public function unidades(Request $r)
    {
        if ($r->filled('id')) {
            $row = DB::table('clave_unidad')->select(['id','clave','descripcion'])->where('id', $r->id)->first();
            return response()->json($row ? [$row] : []);
        }

        $term = trim($r->get('term', ''));
        $q = DB::table('clave_unidad')->select(['id','clave','descripcion'])->limit(20);

        if ($term !== '') {
            $like = '%'.$term.'%';
            $q->where(function($qq) use ($like){
                $qq->where('clave','like',$like)->orWhere('descripcion','like',$like);
            });
        }

        return response()->json($q->get());
    }
}
