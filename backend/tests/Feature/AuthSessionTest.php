<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_autentica_com_sessao_sem_emitir_token_persistente(): void
    {
        $user = User::factory()->create([
            'email' => 'operador@example.test',
            'password' => 'senha-segura',
            'perfil' => 'operador',
        ]);
        $this->withHeader('Origin', 'http://localhost:3000');

        $this->postJson('/api/login', [
            'email' => 'operador@example.test',
            'senha' => 'senha-segura',
        ])->assertOk()
            ->assertJsonPath('usuario.id', $user->id)
            ->assertJsonMissingPath('token');

        $this->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/logout')->assertOk();
        Auth::forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_login_aplica_limite_de_tentativas(): void
    {
        for ($tentativa = 0; $tentativa < 5; $tentativa++) {
            $this->postJson('/api/login', [
                'email' => 'inexistente@example.test',
                'senha' => 'senha-invalida',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'inexistente@example.test',
            'senha' => 'senha-invalida',
        ])->assertStatus(429);
    }

    public function test_logout_revoga_tokens_legados_do_sanctum(): void
    {
        $user = User::factory()->create(['perfil' => 'admin']);
        $token = $user->createToken('legacy-api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
