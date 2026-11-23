<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerguntaTest extends TestCase
{
    use RefreshDatabase;

    private function evento(string $titulo = 'Evento'): Evento
    {
        return Evento::create(['titulo' => $titulo]);
    }

    public function test_rejeita_texto_vazio_com_422(): void
    {
        $evento = $this->evento();

        $this->actingAs(User::factory()->create())
            ->postJson("/eventos/{$evento->id}/perguntas", ['texto' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('texto');

        $this->assertDatabaseCount('perguntas', 0);
    }

    public function test_rejeita_texto_de_uma_letra_com_422(): void
    {
        $evento = $this->evento();

        $this->actingAs(User::factory()->create())
            ->postJson("/eventos/{$evento->id}/perguntas", ['texto' => 'a'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('texto');
    }

    public function test_rejeita_texto_acima_de_255_caracteres(): void
    {
        $evento = $this->evento();

        $this->actingAs(User::factory()->create())
            ->postJson("/eventos/{$evento->id}/perguntas", ['texto' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('texto');
    }

    public function test_rejeita_evento_inexistente_com_422(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/eventos/999/perguntas', ['texto' => 'Uma pergunta válida e longa'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('evento_id');
    }

    public function test_aceita_pergunta_valida(): void
    {
        $evento = $this->evento();

        $this->actingAs(User::factory()->create())
            ->post("/eventos/{$evento->id}/perguntas", ['texto' => 'Como funciona o Service Container?'])
            ->assertRedirect(route('eventos.show', $evento->id));

        $this->assertDatabaseHas('perguntas', [
            'evento_id' => $evento->id,
            'texto'     => 'Como funciona o Service Container?',
        ]);
    }

    public function test_show_pagina_filtra_por_evento_e_ordena_pelas_mais_recentes(): void
    {
        $evento  = $this->evento('Principal');
        $outro   = $this->evento('Secundário');

        for ($i = 1; $i <= 25; $i++) {
            $p = Pergunta::create(['evento_id' => $evento->id, 'is_public' => true, 'texto' => "Pergunta {$i} do evento principal"]);
            $p->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save();
        }
        for ($i = 1; $i <= 3; $i++) {
            Pergunta::create(['evento_id' => $outro->id, 'is_public' => true, 'texto' => "Pergunta {$i} de outro evento"]);
        }

        $resp = $this->get("/eventos/{$evento->id}")->assertOk();
        $perguntas = $resp->viewData('perguntas');

        $this->assertCount(10, $perguntas);
        $this->assertSame(25, $perguntas->total());
        $this->assertTrue($perguntas->every(fn ($p) => $p->evento_id === $evento->id));
        $this->assertSame('Pergunta 25 do evento principal', $perguntas->first()->texto);
        $resp->assertSee('pagination', false);

        $ultima = $this->get("/eventos/{$evento->id}?page=3")->viewData('perguntas');
        $this->assertCount(5, $ultima);
    }

    public function test_show_de_evento_inexistente_retorna_404(): void
    {
        $this->get('/eventos/999')->assertNotFound();
    }
}
