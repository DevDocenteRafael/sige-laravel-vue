<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function atualizarPerfil(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'nome'  => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'foto'         => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'remover_foto' => 'nullable|boolean',
        ]);

        $usuario->name = $validated['nome'];

        $usuario->email = $validated['email'];

        if ($request->hasFile('foto')) {
            if ($usuario->foto_url) {
                $caminhoAntigo = public_path('storage/' . $usuario->foto_url);
                if (file_exists($caminhoAntigo)) {
                    unlink($caminhoAntigo);
                }
            }

            $arquivo = $request->file('foto');
            $nome = time() . '_' . uniqid() . '.' . $arquivo->getClientOriginalExtension();
            $arquivo->storeAs('usuarios/fotos', $nome, 'public');
            $usuario->foto_url = 'usuarios/fotos/' . $nome;
        } elseif ($request->input('remover_foto')) {
            if ($usuario->foto_url) {
                $caminhoAntigo = public_path('storage/' . $usuario->foto_url);
                if (file_exists($caminhoAntigo)) {
                    unlink($caminhoAntigo);
                }
            }
            $usuario->foto_url = null;
        }

        $usuario->save();

       return response()->json([
    'message' => 'Perfil atualizado com sucesso!',
    'usuario' => [
        'name'     => $usuario->name,
        'email'    => $usuario->email,
        'foto_url' => $usuario->foto_url ? asset('storage/' . $usuario->foto_url) : null,
    ],
]);
    }

    public function atualizarSenha(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'senha_atual'             => 'required|string',
            'nova_senha'              => 'required|string|min:6',
            'nova_senha_confirmation' => 'required|string|same:nova_senha',
        ]);

        if (!password_verify($validated['senha_atual'], $usuario->password)) {
            return response()->json([
                'message' => 'Senha atual incorreta.',
            ], 422);
        }

        $usuario->password = bcrypt($validated['nova_senha']);
        $usuario->save();

        return response()->json([
            'message' => 'Senha atualizada com sucesso!',
        ]);
    }
}