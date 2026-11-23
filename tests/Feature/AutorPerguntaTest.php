<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutorPerguntaTest extends TestCase
{
    use RefreshDatabase;

    public function test_relacionamentos_user_e_perguntas(): void
    {
        $user = User::factory()->create();
        $evento = Evento::create(['titulo' => 'Evento']);
        $pergunta = Pergunta::create([
            'evento_id' => $evento->id,
            'user_id'   => $user->id,
            'texto'     => 'Pergunta com autor definido',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $pergunta->user());
        $this->assertInstanceOf(HasMany::class, $user->perguntas());
        $this->assertTrue($pergunta->user->is($user));
        $this->assertTrue($user->perguntas->first()->is($pergunta));
    }

    public function test_view_exibe_nome_do_autor_ou_anonimo(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);
        $user = User::factory()->create(['name' => 'Maria Palestrante']);

        Pergunta::create(['evento_id' => $evento->id, 'is_public' => true, 'user_id' => $user->id, 'texto' => 'Pergunta com autor definido']);
        Pergunta::create(['evento_id' => $evento->id, 'is_public' => true, 'user_id' => null, 'texto' => 'Pergunta sem autor nenhum']);

        $this->get("/eventos/{$evento->id}")
            ->assertOk()
            ->assertSee('Maria Palestrante')
            ->assertSee('Anônimo');
    }

    public function test_show_nao_dispara_n_mais_1_nos_autores(): void
    {
        $evento = Evento::create(['titulo' => 'Evento']);

        foreach (User::factory()->count(10)->create() as $i => $user) {
            Pergunta::create([
                'evento_id' => $evento->id,
                'is_public' => true,
                'user_id'   => $user->id,
                'texto'     => "Pergunta número {$i} com autor próprio",
            ]);
        }

        DB::enableQueryLog();
        $this->get("/eventos/{$evento->id}")->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        // 10 autores diferentes, mas só UMA consulta de carga de users (eager loading)
        $this->assertSame(
            1,
            $queries->filter(fn ($q) => str_starts_with($q, 'select * from "users"'))->count(),
            "Esperava 1 consulta a users, queries: \n" . $queries->implode("\n")
        );
    }
}
