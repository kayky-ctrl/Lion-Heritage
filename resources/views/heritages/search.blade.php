@extends('layouts.app')

@section('title', 'Search Results')

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6">Resultados para: "{{ $query }}"</h2>

    @if(!empty($results) && count($results) > 0)
        <div class="grid gap-4">
            @foreach($results as $post)
                <div class="p-6 border-b border-gray-200"> {{-- Removido o estilo de card --}}
                    <h3 class="text-xl font-semibold mb-2">
                        <a href="{{ url('01_module_c/heritages/' . $post['date'] . '-' . Illuminate\Support\Str::slug($post['title'])) }}" 
                           class="text-blue-600 hover:underline">
                            {{ $post['title'] }}
                        </a>
                    </h3>
                    
                    @if (!empty($post['summary']))
                        <p class="text-gray-600 mb-2">{{ $post['summary'] }}</p>
                    @endif
                    
                    <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($post['date'])->format('d/m/Y') }}</p>
                    
                    @if (!empty($post['tags']) && is_array($post['tags']) && count($post['tags']) > 0)
                        <div class="mt-2">
                            @foreach ($post['tags'] as $tag)
                                @if (!empty(trim($tag)))
                                    <a href="{{ url('01_module_c/tags/' . urlencode($tag)) }}" 
                                       class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2 mb-2">
                                        {{ $tag }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="p-6 text-center border-b border-gray-200">
            <p class="text-gray-600 text-lg">Nenhum resultado encontrado.</p>
            <p class="text-sm text-gray-500 mt-2">Tente usar palavras-chave diferentes ou verifique a ortografia.</p>
            <a href="{{ url('01_module_c') }}" class="text-blue-600 hover:underline mt-4 inline-block">
                Voltar para a página inicial
            </a>
        </div>
    @endif
</div>
@endsection