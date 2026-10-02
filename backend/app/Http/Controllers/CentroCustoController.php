<?php

namespace App\Http\Controllers;

use App\Models\CentroCusto;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CentroCustoController extends Controller
{
    public function index()
    {
        return CentroCusto::orderBy('codigo')->get();
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'codigo' => 'required|string|max:20|unique:centro_custo,codigo',
            'nome'   => 'required|string|max:100',
        ]);

        return response()->json(CentroCusto::create($dados), 201);
    }

    public function update(Request $request, CentroCusto $centroCusto)
    {
        $dados = $request->validate([
            'codigo' => "required|string|max:20|unique:centro_custo,codigo,{$centroCusto->id_centro_custo},id_centro_custo",
            'nome'   => 'required|string|max:100',
            'ativo'  => 'boolean',
        ]);

        $centroCusto->update($dados);

        return $centroCusto;
    }

    public function destroy(CentroCusto $centroCusto)
    {
        try {
            $centroCusto->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Este centro de custo está em uso. Desative-o em vez de excluir.',
                ], 409);
            }
            throw $e;
        }

        return response()->noContent();
    }
}