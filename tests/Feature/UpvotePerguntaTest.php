<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpvotePerguntaTest extends TestCase
{
    use RefreshDatabase;

    private function pergunta(array $attrs = []): Pergunta
    {
        $evento = Evento::first() ?? Evento::create(['titulo' => 'Evento']);

        return Pergunta::create($attrs + [
            'evento_id' => $evento->id,
            'is_public' => true,
            'texto'     => 'Pergunta de teste com texto longo',
        ]);
    }

    public function test_relacionamentos_belongs_to_many(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->pergunta();

        $this->assertInstanceOf(BelongsToMany::class, $pergunta->votos());
        $this->assertInstanceOf(BelongsToMany::class, $user->perguntasVotadas());

        $pergunta->votos()->attach($user);

        $this->assertTrue($pergunta->votos->first()->is($user));
        $this->assertTrue($user->perguntasVotadas->first()->is($pergunta));
    }

    public function test_pivot_impede_voto_duplicado(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->pergunta();

        $pergunta->votos()->attach($user);

        $this->expectException(QueryException::class);
        $pergunta->votos()->attach($user);
    }

    public function test_anonimo_nao_pode_votar(): void
    {
        $pergunta = $this->pergunta();

        $this->post(route('perguntas.votar', $pergunta))->assertRedirect(route('login.create'));
        $this->postJson(route('perguntas.votar', $pergunta))->assertStatus(401);

        $this->assertDatabaseCount('pergunta_user', 0);
    }

    public function test_votar_insere_e_votar_de_novo_remove(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->pergunta();

        $this->actingAs($user)->from('/')->post(route('perguntas.votar', $pergunta))
            ->assertRedirect('/')
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);

        $this->actingAs($user)->from('/')->post(route('perguntas.votar', $pergunta))->assertRedirect('/');

        $this->assertDatabaseMissing('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
    }

    public function test_perguntas_sao_ordenadas_por_votos_decrescente(): void
    {
        $users = User::factory()->count(3)->create();
        $umVoto   = $this->pergunta(['texto' => 'Pergunta com um voto só']);
        $tresVotos = $this->pergunta(['texto' => 'Pergunta com três votos']);
        $doisVotos = $this->pergunta(['texto' => 'Pergunta com dois votos']);
        $semVotos = $this->pergunta(['texto' => 'Pergunta sem nenhum voto']);

        $umVoto->votos()->attach($users->take(1)->pluck('id'));
        $tresVotos->votos()->attach($users->pluck('id'));
        $doisVotos->votos()->attach($users->take(2)->pluck('id'));

        $perguntas = $this->get('/eventos/' . $umVoto->evento_id)->assertOk()->viewData('perguntas');

        $this->assertSame(
            [$tresVotos->id, $doisVotos->id, $umVoto->id, $semVotos->id],
            $perguntas->pluck('id')->all()
        );
        $this->assertSame(3, $perguntas->first()->votos_count);
    }

    public function test_botao_destacado_quando_usuario_ja_votou(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->pergunta();

        $this->actingAs($user)->get('/eventos/' . $pergunta->evento_id)
            ->assertSee('Votar')
            ->assertDontSee('Remover voto');

        $pergunta->votos()->attach($user);

        $this->actingAs($user)->get('/eventos/' . $pergunta->evento_id)
            ->assertSee('Remover voto');
    }
}
