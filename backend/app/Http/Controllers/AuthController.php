<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required',
        ]);

        $credentials = [
            'email'    => $request->email,
            'password' => $request->senha,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $user = Auth::user();

        if (!$user->ativo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Usuário inativo. Procure um administrador.'], 403);
        }

        $request->session()->regenerate();

        AuditHelper::log('Login', 'Usuário ' . $user->name . ' (' . $user->email . ') realizou login');

        return response()->json([
            'usuario' => [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'perfil'  => $user->perfil ?? 'visualizador',
                'foto_url'=> $user->foto_url
                    ? asset('storage/' . $user->foto_url)
                    : null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        AuditHelper::log('Logout', 'Usuário ' . $user->name . ' (' . $user->email . ') realizou logout');

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }
}