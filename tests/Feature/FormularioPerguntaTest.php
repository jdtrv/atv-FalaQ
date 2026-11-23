<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormularioPerguntaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pergunta_em_branco_exibe_erro_em_vermelho(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);
        $url = "/eventos/{$evento->id}";

        $this->actingAs(User::factory()->create())
            ->from($url)
            ->post("{$url}/perguntas", ['texto' => ''])
            ->assertRedirect($url)
            ->assertSessionHasErrors('texto');

        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->from($url)
            ->post("{$url}/perguntas", ['texto' => ''])
            ->assertSee('A pergunta é obrigatória.')
            ->assertSee('text-red-500', false)
            ->assertSee('border-red-500', false);
    }

    public function test_texto_digitado_nao_e_perdido_apos_falha_de_validacao(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);
        $url = "/eventos/{$evento->id}";

        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->from($url)
            ->post("{$url}/perguntas", ['texto' => 'xyz123abc'])
            ->assertSee('xyz123abc')
            ->assertSee('no mínimo 10 caracteres');
    }

    public function test_botao_enviar_usa_classes_tailwind(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);

        $this->actingAs(User::factory()->create())
            ->get("/eventos/{$evento->id}")
            ->assertSee('bg-blue-600 text-white', false)
            ->assertSee('rounded-md hover:bg-blue-700', false);
    }
}
