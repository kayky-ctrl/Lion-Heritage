@extends('layouts.app')

@section('title', 'Tags')

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6">Tags</h2>

    @if (!empty($allTags) && count($allTags) > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($allTags as $tag)
                @if (!empty(trim($tag)))
                    <a href="{{ url('01_module_c/tags/' . urlencode($tag)) }}" 
                       class="text-blue-600 hover:underline p-4"> {{-- Removido o estilo de card --}}
                        {{ $tag }}
                    </a>
                @endif
            @endforeach
        </div>
    @else
        <div class="p-6 text-center">
            <p class="text-gray-600 text-lg">Nenhuma tag encontrada.</p>
            <p class="text-sm text-gray-500 mt-2">As tags aparecerão automaticamente quando forem adicionadas aos posts.</p>
        </div>
    @endif
</div>
@endsection