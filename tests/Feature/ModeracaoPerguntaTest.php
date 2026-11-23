<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeracaoPerguntaTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonimo_nao_envia_pergunta(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);
        $dados = ['texto' => 'Uma pergunta válida e longa'];

        $this->post("/eventos/{$evento->id}/perguntas", $dados)->assertRedirect(route('login.create'));
        $this->postJson("/eventos/{$evento->id}/perguntas", $dados)->assertStatus(401);

        $this->assertDatabaseCount('perguntas', 0);
    }

    public function test_perguntas_pendentes_ficam_escondidas_do_publico(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);
        Pergunta::create(['evento_id' => $evento->id, 'is_public' => true,  'texto' => 'Pergunta aprovada visível']);
        Pergunta::create(['evento_id' => $evento->id, 'is_public' => false, 'texto' => 'Pergunta pendente escondida']);

        $resp = $this->get("/eventos/{$evento->id}")
            ->assertOk()
            ->assertSee('Pergunta aprovada visível')
            ->assertDontSee('Pergunta pendente escondida');

        $this->assertSame(1, $resp->viewData('perguntas')->total());
    }

    public function test_pergunta_nova_nasce_pendente_e_nao_aparece(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);

        $this->actingAs(User::factory()->create())
            ->post("/eventos/{$evento->id}/perguntas", ['texto' => 'Minha nova pergunta para moderar'])
            ->assertRedirect(route('eventos.show', $evento->id));

        $this->assertDatabaseHas('perguntas', ['texto' => 'Minha nova pergunta para moderar', 'is_public' => false]);

        $this->get("/eventos/{$evento->id}")->assertDontSee('Minha nova pergunta para moderar');
    }
}
