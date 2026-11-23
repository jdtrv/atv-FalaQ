@extends('layouts.app')

@section('title', $evento->titulo . ' — FalaQ')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-5 gap-6">

    <!-- Formulário de envio de Pergunta -->
    <div class="md:col-span-2">
        <div class="bg-[#1a1d27] border border-[#2e3347] rounded-lg shadow-sm p-5">
            <h4 class="text-xl font-bold mb-4">💬 Faça sua Pergunta</h4>

            @auth
                <form action="{{ route('eventos.perguntas.store', $evento->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="texto" class="block text-sm text-gray-400 mb-1">Texto da Pergunta</label>

                        <textarea name="texto" id="texto" rows="4"
                                  class="w-full rounded-md bg-gray-900 text-white p-3 border focus:outline-none @error('texto') border-red-500 @else border-gray-600 @enderror"
                                  placeholder="Digite sua dúvida ou comentário para o palestrante...">{{ old('texto') }}</textarea>

                        @error('texto')
                            <p class="text-red-500 text-sm font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 text-white font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Enviar Pergunta
                    </button>
                </form>
            @else
                <p class="text-gray-400">
                    Para enviar uma pergunta você precisa estar logado.
                    <a href="{{ route('login.create') }}" class="text-blue-400 underline hover:text-blue-300">Entrar</a>
                    ou
                    <a href="{{ route('register.create') }}" class="text-blue-400 underline hover:text-blue-300">criar conta</a>.
                </p>
            @endauth
        </div>
    </div>

    <!-- Mural de Perguntas (ranking por votos) -->
    <div class="md:col-span-3">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-xl font-bold">📋 Perguntas do Evento</h4>
            <span class="text-gray-400 text-sm">Total de perguntas: {{ $perguntas->total() }}</span>
        </div>

        @forelse($perguntas as $pergunta)
            <div class="mb-4 p-4 bg-gray-800 text-white rounded-2xl rounded-tl-sm shadow">
                <p class="text-lg mb-1">{{ $pergunta->texto }}</p>
                <p class="text-gray-400 text-sm mb-3">👤 {{ $pergunta->user->name ?? 'Anônimo' }}</p>

                <div class="flex justify-between items-center text-gray-400 text-sm">
                    <div class="flex items-center gap-3">
                        @auth
                            <form action="{{ route('perguntas.votar', $pergunta) }}" method="POST">
                                @csrf
                                @if($pergunta->votado)
                                    <button type="submit" title="Clique para remover seu voto"
                                            class="bg-blue-600 text-white font-semibold px-3 py-1 rounded-full hover:bg-blue-700 transition-colors">
                                        ▲ Remover voto · {{ $pergunta->votos_count }}
                                    </button>
                                @else
                                    <button type="submit" title="Clique para votar"
                                            class="border border-blue-500 text-blue-400 px-3 py-1 rounded-full hover:bg-blue-600 hover:text-white transition-colors">
                                        △ Votar · {{ $pergunta->votos_count }}
                                    </button>
                                @endif
                            </form>
                        @else
                            <span class="border border-gray-600 px-3 py-1 rounded-full">▲ {{ $pergunta->votos_count }} votos</span>
                        @endauth
                        <span>Status: <span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded">{{ $pergunta->status }}</span></span>
                    </div>
                    <span>{{ $pergunta->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        @empty
            <div class="text-center text-gray-400 bg-gray-800 rounded-lg p-6">
                Nenhuma pergunta aprovada ainda. Seja o primeiro!
            </div>
        @endforelse

        <!-- Paginação -->
        <div class="flex justify-center mt-6">
            {{ $perguntas->links() }}
        </div>
    </div>
</div>
@endsection
